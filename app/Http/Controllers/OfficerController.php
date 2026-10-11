<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Officer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OfficerController extends Controller
{
    public function index()
    {
        return view('admin.officers', ['officers' => Officer::with('photo')->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.officer-form', ['officer' => new Officer]);
    }

    public function edit(Officer $officer)
    {
        return view('admin.officer-form', ['officer' => $officer]);
    }

    public function store(Request $r)
    {
        return $this->save($r, new Officer);
    }

    public function update(Request $r, Officer $officer)
    {
        return $this->save($r, $officer);
    }

    private function save(Request $r, Officer $officer)
    {
        $data = $r->validate(['name' => 'required|string|max:160', 'position' => 'required|string|max:160', 'term' => 'nullable|string|max:160', 'biography' => 'nullable|string|max:10000', 'email' => 'nullable|email|max:255', 'social_url' => 'nullable|url:http,https|max:255', 'photo_upload' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120', 'sort_order' => 'required|integer|min:0|max:10000', 'status' => 'required|in:draft,published']);
        unset($data['photo_upload']);
        if ($r->hasFile('photo_upload')) {
            $path = $r->file('photo_upload')->store('cms', 'public');
            $data['photo_id'] = Media::create(['path' => $path, 'name' => 'Officer portrait', 'alt' => $data['name']])->id;
        }
        $officer->fill($data)->save();

        return redirect()->route('admin.officers.edit', $officer)->with('success', 'Officer saved.');
    }

    public function destroy(Officer $officer)
    {
        $officer->delete();

        return back()->with('success', 'Officer deleted.');
    }
}
