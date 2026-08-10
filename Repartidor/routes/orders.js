const express = require('express');
const router = express.Router();
const api = require('../services/api');
const authMiddleware = require('../middleware/auth');

router.get('/', authMiddleware, async (req, res) => {
  try {
    const { status, date, search } = req.query;
    let path = '/api/repartidor/pedidos';
    const params = [];
    if (status) params.push(`status=${encodeURIComponent(status)}`);
    if (date) params.push(`date=${encodeURIComponent(date)}`);
    if (search) params.push(`search=${encodeURIComponent(search)}`);
    if (params.length > 0) path += '?' + params.join('&');
    const data = await api.get(path);
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/today', authMiddleware, async (req, res) => {
  try {
    const today = new Date().toISOString().slice(0, 10);
    const data = await api.get(`/api/repartidor/pedidos?date=${today}`);
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/:id', authMiddleware, async (req, res) => {
  try {
    const data = await api.get(`/api/repartidor/pedidos/${req.params.id}`);
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.post('/', authMiddleware, async (req, res) => {
  try {
    const data = await api.post('/api/repartidor/pedidos', req.body);
    const io = req.app.get('io');
    if (io) io.emit('order:created', data);
    res.status(201).json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.put('/:id', authMiddleware, async (req, res) => {
  try {
    const data = await api.put(`/api/repartidor/pedidos/${req.params.id}/estado`, req.body);
    const io = req.app.get('io');
    if (io) io.emit('order:statusChanged', data);
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.delete('/:id', authMiddleware, async (req, res) => {
  try {
    await api.del(`/api/repartidor/pedidos/${req.params.id}`);
    res.json({ message: 'Pedido eliminado' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
