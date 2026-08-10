const express = require('express');
const router = express.Router();
const api = require('../services/api');
const authMiddleware = require('../middleware/auth');

router.get('/stats', authMiddleware, async (req, res) => {
  try {
    const data = await api.get('/api/repartidor/dashboard/stats');
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/recent-orders', authMiddleware, async (req, res) => {
  try {
    const data = await api.get('/api/repartidor/dashboard/recent-orders');
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/low-stock', authMiddleware, async (req, res) => {
  try {
    const data = await api.get('/api/repartidor/dashboard/low-stock');
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
