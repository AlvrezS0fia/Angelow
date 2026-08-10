ALTER TABLE pedidos 
MODIFY COLUMN estado ENUM('pendiente','asignado','confirmado','procesando','listo','en_camino','entregado','cancelado','reembolsado') DEFAULT 'pendiente';
