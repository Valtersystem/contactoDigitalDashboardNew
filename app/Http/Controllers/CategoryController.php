<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    /**
     * Mostra uma lista de categorias.
     */
    public function index(): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => Category::latest()->paginate(10)
        ]);
    }

    /**
     * Mostra o formulário para criar uma nova categoria.
     */
    public function create(): Response
    {
        return Inertia::render('Categories/Create');
    }

    /**
     * Guarda uma nova categoria na base de dados.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoria criada com sucesso.');
    }

    /**
     * Mostra o formulário para editar uma categoria.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('Categories/Edit', [
            'category' => $category
        ]);
    }

    /**
     * Atualiza uma categoria na base de dados.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories')->ignore($category->id)],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoria atualizada com sucesso.');
    }

    /**
     * Apaga uma categoria.
     */
    public function destroy(Category $category): RedirectResponse
    {
        // Verifica se a categoria tem produtos associados
        if ($category->products()->exists()) {
            return back()->with('error', 'Não é possível apagar uma categoria que está a ser utilizada por produtos.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Categoria apagada com sucesso.');
    }
}
