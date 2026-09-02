const api = require('../services/api');

module.exports = async (req, res, next) => {
  const token = req.headers.authorization?.replace('Bearer ', '') ||
                req.session?.token;

  if (!token) {
    return res.status(401).json({ error: 'No autorizado' });
  }

  api.setToken(token);

  try {
    const result = await api.get('/api/repartidor/auth/me');
    if (!result.success) {
      return res.status(401).json({ error: 'Token inválido' });
    }
    req.repartidor = result.user;
    req.documentos = result.documentos;
    req.repartidorToken = token;
    next();
  } catch (err) {
    return res.status(401).json({ error: 'Token inválido o expirado' });
  }
};
