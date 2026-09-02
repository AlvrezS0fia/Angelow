const express = require('express');
const router = express.Router();
const api = require('../services/api');
const authMiddleware = require('../middleware/auth');

router.post('/ubicacion', authMiddleware, async (req, res) => {
  try {
    const { repartidor_id, pedido_id, latitud, longitud, velocidad_kmh, bateria_porcentaje } = req.body;
    if (!repartidor_id || !pedido_id || latitud == null || longitud == null) {
      return res.status(400).json({ error: 'repartidor_id, pedido_id, latitud y longitud requeridos' });
    }
    const result = await api.post('/api/repartidor/seguimiento/ubicacion', {
      repartidor_id, pedido_id, latitud, longitud, velocidad_kmh, bateria_porcentaje,
    });
    res.json(result);
    const io = req.app.get('io');
    if (io) {
      io.emit('tracking:update', { pedido_id, latitud, longitud, timestamp: new Date().toISOString() });
    }
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/:pedidoId', authMiddleware, async (req, res) => {
  try {
    const result = await api.get(`/api/repartidor/seguimiento/${req.params.pedidoId}`);
    res.json(result);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
