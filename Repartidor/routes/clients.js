const express = require('express');
const router = express.Router();
const api = require('../services/api');
const authMiddleware = require('../middleware/auth');

router.get('/', authMiddleware, async (req, res) => {
  try {
    const { search } = req.query;
    let path = '/api/repartidor/clientes';
    if (search) path += `?search=${encodeURIComponent(search)}`;
    const data = await api.get(path);
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/:id', authMiddleware, async (req, res) => {
  try {
    const data = await api.get(`/api/repartidor/clientes/${req.params.id}`);
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
