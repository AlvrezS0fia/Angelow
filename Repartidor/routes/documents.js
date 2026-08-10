const express = require('express');
const router = express.Router();
const path = require('path');
const fs = require('fs');
const multer = require('multer');

const config = require('../config');
const api = require('../services/api');
const authMiddleware = require('../middleware/auth');

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    const dir = path.join(config.uploadsDir, 'documentos');
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    cb(null, dir);
  },
  filename: (req, file, cb) => {
    const repartidorId = req.repartidor?.id || req.body.repartidor_id || 'unknown';
    const tipo = req.body.tipo || 'doc';
    const ext = path.extname(file.originalname);
    cb(null, `${repartidorId}_${tipo}_${Date.now()}${ext}`);
  },
});

const upload = multer({
  storage,
  limits: { fileSize: 10 * 1024 * 1024 },
  fileFilter: (req, file, cb) => {
    const allowed = ['.pdf', '.jpg', '.jpeg', '.png'];
    const ext = path.extname(file.originalname).toLowerCase();
    if (!allowed.includes(ext)) {
      return cb(new Error('Solo PDF, JPG y PNG'));
    }
    cb(null, true);
  },
});

router.post('/subir', authMiddleware, upload.single('archivo'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'Archivo requerido' });
    }
    const formData = new FormData();
    formData.append('repartidor_id', req.repartidor.id);
    formData.append('tipo', req.body.tipo);
    const blob = new Blob([fs.readFileSync(req.file.path)]);
    formData.append('archivo', blob, req.file.filename);
    const result = await api.upload('/api/repartidor/documentos/subir', formData);
    res.json(result);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/:repartidorId', authMiddleware, async (req, res) => {
  try {
    const result = await api.get(`/api/repartidor/documentos/${req.params.repartidorId}`);
    res.json(result);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/', authMiddleware, async (req, res) => {
  try {
    const docs = await api.get(`/api/repartidor/documentos/${req.repartidor.id}`);
    res.json(docs);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
