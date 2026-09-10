<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * ============================================================
 * ARCHIVO: UsuarioModel.php — MÓDULO: Modelo de usuarios
 * ============================================================
 * QUÉ HACE: CRUD de usuarios con gestión de autenticación (login por email,
 *           tokens de recuperación, cambio de contraseña). getAll() NO expone
 *           password_hash ni reset_token por seguridad.
 * TABLA(S): usuarios
 * QUIÉN LO USA: AuthController, Admin\ClientesController,
 *               Cliente\PerfilApiController, RepartidorAuthController,
 *               Api\RepartidorRegistroController
 */
// ENCAPSULAMIENTO: la conexión queda oculta a los controladores; solo se la
// usa aquí dentro, aislando el acceso a la tabla `usuarios`.
class UsuarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // --- BUSCAR USUARIO POR EMAIL ---
    // Entrada: email (normalizado en el controlador).
    // Salida: fila completa de `usuarios` (incluye rol, estado, password_hash)
    //         o false. Se usa en login, registro y recuperación de contraseña.
    /** @param string $email
     * @return array<string, mixed>|false */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    // --- BUSCAR USUARIO POR ID ---
    // Entrada: id (normalmente de $_SESSION['user']['id'] o de payload JWT 'sub').
    // Salida: fila completa o false. Se usa para refrescar datos del perfil.
    /** @param int|string $id
     * @return array<string, mixed>|false */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // El modelo consulta la tabla:usuarios
    // --- CREAR USUARIO ---
    // Entrada: array $data con email, nombre, password_hash, rol, estado, etc.
    // Procesamiento: INSERT con sentencia preparada (valores nunca en el SQL).
    //   Si no se pasa rol → 'cliente' por defecto. Si no se pasa estado → 'activo'.
    // ROLES: el rol lo define quien crea (register → 'cliente', admin store → el que elija).
    /** @param array<string, mixed> $data
     * @return int|false */
    public function create($data) {
        $email = $data['email'] ?? null;
        $nombre = $data['nombre'] ?? null;
        $apellido = $data['apellido'] ?? null;
        $password_hash = $data['password_hash'] ?? null;
        $rol = $data['rol'] ?? 'cliente';
        $telefono = $data['telefono'] ?? null;
        $direccion = $data['direccion'] ?? null;
        $ciudad = $data['ciudad'] ?? null;
        $tipo_documento = $data['tipo_documento'] ?? null;
        $tipo_vehiculo = $data['tipo_vehiculo'] ?? null;
        $placa_vehiculo = $data['placa_vehiculo'] ?? null;
        $estado = $data['estado'] ?? 'activo';
        $acepta_terminos = isset($data['acepta_terminos']) ? ($data['acepta_terminos'] ? 1 : 0) : 0;
        $fecha_registro = $data['fecha_registro'] ?? date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO usuarios (email, nombre, apellido, password_hash, rol, telefono, direccion, ciudad, tipo_documento, tipo_vehiculo, placa_vehiculo, estado, acepta_terminos, fecha_registro) 
                VALUES (:email, :nombre, :apellido, :password_hash, :rol, :telefono, :direccion, :ciudad, :tipo_documento, :tipo_vehiculo, :placa_vehiculo, :estado, :acepta_terminos, :fecha_registro)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            'email' => $email,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'password_hash' => $password_hash,
            'rol' => $rol,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'ciudad' => $ciudad,
            'tipo_documento' => $tipo_documento,
            'tipo_vehiculo' => $tipo_vehiculo,
            'placa_vehiculo' => $placa_vehiculo,
            'estado' => $estado,
            'acepta_terminos' => $acepta_terminos,
            'fecha_registro' => $fecha_registro
        ]);

        return $result ? (int) $this->db->lastInsertId() : false;
    }

    // --- ACTUALIZAR CONTRASEÑA ---
    // Entrada: email + hash bcrypt ya generado (password_hash en el controlador).
    // Salida: bool. Se usa en resetPassword/changePassword de AuthController.
    /** @param string $email
     * @param string $passwordHash
     * @return bool */
    public function updatePassword($email, $passwordHash) {
        $stmt = $this->db->prepare("UPDATE usuarios SET password_hash = :pwd WHERE email = :email");
        return $stmt->execute(['pwd' => $passwordHash, 'email' => $email]);
    }

    // --- GUARDAR TOKEN DE RECUPERACIÓN ---
    // Entrada: email + token binario + fecha de expiración (1 hora).
    // Se usa en AuthController::forgotPassword antes de enviar el correo.
    /** @param string $email
     * @param string $token
     * @param string $expires
     * @return bool */
    public function setResetToken($email, $token, $expires) {
        $stmt = $this->db->prepare("UPDATE usuarios SET reset_token = :token, reset_expiry = :expires WHERE email = :email");
        return $stmt->execute(['token' => $token, 'expires' => $expires, 'email' => $email]);
    }

    // --- BUSCAR POR TOKEN DE RECUPERACIÓN ---
    // La condición reset_expiry > NOW() valida la caducidad DENTRO del SQL.
    // Salida: fila de usuario o false (token inválido o vencido).
    /** @param string $token
     * @return array<string, mixed>|false */
    public function findByResetToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE reset_token = :token AND reset_expiry > NOW()");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }

    // --- LIMPIAR TOKEN DE RECUPERACIÓN ---
    // Se invoca tras resetear la contraseña: elimina el token para que no se
    // reúse. (Buena práctica de seguridad anti reutilización de tokens.)
    /** @param string $email
     * @return bool */
    public function clearResetToken($email) {
        $stmt = $this->db->prepare("UPDATE usuarios SET reset_token = NULL, reset_expiry = NULL WHERE email = :email");
        return $stmt->execute(['email' => $email]);
    }

    // --- LISTAR USUARIOS (panel admin) ---
    // Retorna campos operativos (sin password_hash ni reset_token → no se
    // exponen secretos en la API). Se consume en Admin\ClientesController::index.
    // ROLES: todos (cliente, repartidor, administrador) aparecen juntos; el
    //        admin filtra/ordena en la vista.
    /** @return array<int, array<string, mixed>> */
    public function getAll(): array {
        $stmt = $this->db->query("SELECT id, nombre, apellido, email, telefono, rol, estado, tipo_vehiculo, placa_vehiculo, total_entregas, calificacion_promedio, motivo_suspension, fecha_registro FROM usuarios ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    // --- BUSCAR USUARIO POR NOMBRE/EMAIL (panel admin) ---
    // LIKE con comodines %...% → coincide por subcadena. Parametrizado (no SQLi).
    /** @param string $nombre
     * @return array<int, array<string, mixed>> */
    public function buscarPorNombre($nombre) {
        $stmt = $this->db->prepare("SELECT id, nombre, email, telefono, rol, fecha_registro FROM usuarios WHERE nombre LIKE :nombre OR email LIKE :nombre ORDER BY id DESC");
        $stmt->execute(['nombre' => '%' . $nombre . '%']);
        return $stmt->fetchAll();
    }

    //  PUNTO ÚNICO DE CAMBIO DE ROL DEL SISTEMA 
    // --- CAMBIAR ROL DE UN USUARIO ---
    // Entrada: id del usuario objetivo + nuevo rol válido.
    // Procesamiento: UPDATE usuarios SET rol = :rol WHERE id = :id.
    // Quién llama:
    //   - Admin\ClientesController::cambiarRol() → POST /api/clientes/rol
    //     (solo el rol administrador puede invocarlo).
    //   - RepartidorAuthController::registro() → cliente que se convierte en
    //     repartidor.

    //   - La redirección post-login (AuthController::login()).
    //   - El login JWT de la app de repartidor (filtra rol='repartidor').
    // Salida: bool del UPDATE.
    /** @param int|string $id
     * @param string $rol
     * @return bool */
    public function updateRol($id, $rol) {
        $stmt = $this->db->prepare("UPDATE usuarios SET rol = :rol WHERE id = :id");
        return $stmt->execute(['rol' => $rol, 'id' => $id]);
    }

    // --- ELIMINAR USUARIO ---
    // Entrada: id. Se usa en Admin\ClientesController::destroy (DELETE físico).
    // Nota: docs/SEGURIDAD.md recomienda "eliminado" lógico (estado) antes que
    // borrado físico, para conservar historial de pedidos/entregas.
    /** @param int|string $id
     * @return bool */
    public function delete($id): bool {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}