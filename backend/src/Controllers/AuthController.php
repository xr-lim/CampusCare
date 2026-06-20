<?php
namespace App\Controllers;

use App\Config\Database;
use App\Controllers\BaseController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use PDO;
use Exception;

class AuthController extends BaseController {
    
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function register(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        $username = isset($data['username']) ? trim($data['username']) : '';
        $email = isset($data['email']) ? trim($data['email']) : '';
        $password = isset($data['password']) ? $data['password'] : '';
        $role = isset($data['role']) ? $data['role'] : 'Student/Staff';

        if (empty($username) || empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['message' => 'All fields are required.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse($response, ['message' => 'Invalid email address format.'], 400);
        }

        if (strlen($password) < 6) {
            return $this->jsonResponse($response, ['message' => 'Password must be at least 6 characters.'], 400);
        }

        $allowedRoles = ['Student/Staff', 'Technician', 'Admin'];
        if (!in_array($role, $allowedRoles)) {
            return $this->jsonResponse($response, ['message' => 'Invalid account role type assignment.'], 400);
        }

        try {
            $checkStmt = $this->db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $checkStmt->execute(['username' => $username, 'email' => $email]);
            
            if ($checkStmt->rowCount() > 0) {
                return $this->jsonResponse($response, ['message' => 'Username or Email is already registered.'], 409);
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $insertStmt = $this->db->prepare(
                "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)"
            );
            $insertStmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role
            ]);

            return $this->jsonResponse($response, ['message' => 'User registered successfully.'], 201);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ['message' => 'Server error during registration: ' . $e->getMessage()], 500);
        }
    }

    public function login(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        $username = isset($data['username']) ? trim($data['username']) : '';
        $password = isset($data['password']) ? $data['password'] : '';

        if (empty($username) || empty($password)) {
            return $this->jsonResponse($response, ['message' => 'Username and password are required.'], 400);
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                
                $secretKey = getenv('JWT_SECRET') ?: 'fallback_local_secret_key_12345';
                
                $payload = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'iat' => time(),
                    'exp' => time() + (60 * 60 * 8)
                ];

                $jwt = JWT::encode($payload, $secretKey, 'HS256');

                return $this->jsonResponse($response, [
                    'message' => 'Authentication successful.',
                    'token' => $jwt
                ], 200);
                
            } else {
                return $this->jsonResponse($response, ['message' => 'Invalid username or password.'], 401);
            }

        } catch (Exception $e) {
            return $this->jsonResponse($response, ['message' => 'Server error during authentication: ' . $e->getMessage()], 500);
        }
    }

    public function profile(Request $request, Response $response): Response {
        $userPayload = $request->getAttribute('user');
        
        return $this->jsonResponse($response, [
            'message' => 'Authorized access approved.',
            'user' => $userPayload
        ], 200);
    }
}