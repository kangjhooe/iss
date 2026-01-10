<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Indonesia Smart School API",
 *     version="1.0.0",
 *     description="API untuk sistem manajemen sekolah terintegrasi",
 *     @OA\Contact(
 *         email="support@iss.id"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Gunakan token yang didapat dari endpoint /api/v1/login"
 * )
 */
class SwaggerController extends Controller
{
    // This controller is just for Swagger documentation annotations
}
