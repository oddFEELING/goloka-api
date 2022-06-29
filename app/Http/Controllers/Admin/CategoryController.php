<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function categories(){
        $page_title = 'Manage Category';
        $empty_message = 'No category found';
        $categories = Category::latest()->paginate(getPaginate());

        return view('admin.category', compact('page_title', 'empty_message','categories'));
    }

    public function activate(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $category = Category::findOrFail($request->id);
        $category->status = 1;
        $category->save();
        $notify[] = ['success', $category->name . ' has been activated'];
        return back()->withNotify($notify);
    }

    public function deactivate(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $category = Category::findOrFail($request->id);
        $category->status = 0;
        $category->save();
        $notify[] = ['success', $category->name . ' has been disabled'];
        return back()->withNotify($notify);
    }

    public function storeCategory(Request $request){

        $request->validate([
            'name' => 'required|string|max:190|unique:categories'
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->status = 1;
        $category->save();

        $notify[] = ['success', 'Category details has been added'];
        return back()->withNotify($notify);
    }

    public function updateCategory(Request $request,$id){

        $request->validate([
            'name' => 'required|string|max:190|unique:categories,name,'.$id,
        ]);

        $category = Category::findOrFail($id);
        $category->name = $request->name;
        $category->save();

        $notify[] = ['success', 'category details has been Updated'];
        return back()->withNotify($notify);
    }

    public function searchCategory(Request $request)
    {

        $request->validate(['search' => 'required']);
        $search = $request->search;
        $page_title = 'Category Search - ' . $search;
        $empty_message = 'No categories found';
        $categories = Category::where('name', 'like',"%$search%")->paginate(getPaginate());

        return view('admin.category', compact('page_title', 'categories', 'empty_message'));
    }
}
