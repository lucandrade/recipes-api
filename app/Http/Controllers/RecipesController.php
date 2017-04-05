<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Repositories\RecipeRepository;
use Log;
use ApiResponse;

class RecipesController extends BaseController
{

    protected $repository;

    public function __construct(RecipeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function filter(Request $request)
    {
        try {
            $filter = $request->only('sort', 'text', 'page', 'perPage', 'categories');
            $data = $this->repository->filter($filter);
            ApiResponse::setAsSuccess()->setPayload($data);
        } catch (\Exception $e) {
            $message = 'erro ao listar receitas';
            Log::error($message);
            Log::error($e);
            ApiResponse::setAsFail()->setStatusMessage($message);
        }

        return ApiResponse::get();
    }
}
