<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Get all products (public)",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="in_stock",
     *         in="query",
     *         description="Filter by stock availability",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $rules = [
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'in_stock' => 'nullable|string|in:true,false,1,0',
            'search' => 'nullable|string|max:255',
        ];

        $input = $request->all();

        if (isset($input['min_price']) && isset($input['max_price'])) {
            $rules['max_price'] .= '|gt:min_price';
        }

        $validator = Validator::make($input, $rules, [
            'max_price.gt' => 'The max price must be greater than min price'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = Product::query();

        // Apply price range filter
        if ($request->has(['min_price', 'max_price'])) {
            $query->whereBetween('price', [
                $request->min_price,
                $request->max_price
            ]);
        } elseif ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        } elseif ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Apply stock availability filter
        if ($request->has('in_stock')) {
            $inStock = filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN);
            if ($inStock) {
                $query->where('stock_quantity', '>', 0);
            } else {
                $query->where('stock_quantity', '=', 0);
            }
        }

        // Apply search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function($product) {
            $product->price = (float)$product->price;
            return $product;
        });
        return response()->json($products);
    }
}
