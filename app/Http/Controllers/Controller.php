<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Netwrk API Documentation",
 *     version="1.0.0",
 *     description="API documentation for Netwrk platform - User registration and management",
 *     @OA\Contact(
 *         email="support@netwrk.com"
 *     )
 * )
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local development server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}