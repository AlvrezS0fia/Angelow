const express = require('express');
const router = express.Router();
const api = require('../services/api');
const authMiddleware = require('../middleware/auth');

router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) {
      return res.status(400).json({ error: 'Email y contraseña requeridos' });
    }
    const result = await api.post('/api/repartidor/auth/login', { email, password });
    if (!result.success) {
      return res.status(401).json({ error: result.message || 'Credenciales inválidas' });
    }
    api.setToken(result.token);
    if (req.session) req.session.token = result.token;
    res.json({
      success: true,
      token: result.token,
      user: result.user,
      documentos: result.documentos,
    });
  } catch (err) {
    res.status(401).json({ error: err.message || 'Error de autenticación' });
  }
});

router.post('/logout', authMiddleware, async (req, res) => {
  try {
    await api.post('/api/repartidor/auth/logout');
  } catch (e) {}
  api.clearToken();
  if (req.session) req.session.token = null;
  res.json({ success: true });
});

router.get('/me', authMiddleware, async (req, res) => {
  try {
    const result = await api.get('/api/repartidor/auth/me');
    res.json(result);
  } catch (err) {
    res.status(401).json({ error: 'Sesión expirada' });
  }
});

module.exports = router;
