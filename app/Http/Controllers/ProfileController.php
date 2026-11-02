<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        return view('profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'idType' => 'nullable|in:nin,passport',
            'idNumber' => 'nullable|string|max:50',
            'idDocument' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Store new picture
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $validated['profile_picture'] = $path;
        }

        // Handle ID document upload. Identity documents are private assets:
        // they are only served through an authorised controller action.
        if ($request->hasFile('idDocument')) {
            if ($user->id_document) {
                Storage::disk('private')->delete($user->id_document);
                Storage::disk('public')->delete($user->id_document);
            }

            $path = $request->file('idDocument')->store('id-documents', 'private');
            $validated['id_document'] = $path;
        }

        // The upload itself is already stored; never mass-assign the raw file.
        unset($validated['idDocument']);

        // Add ID fields if provided
        if ($request->filled('idType')) {
            $validated['id_type'] = $request->input('idType');
        }
        if ($request->filled('idNumber')) {
            $validated['id_number'] = $request->input('idNumber');
        }

        $user->update($validated);

        return redirect('/profile')->with('success', 'Profile updated successfully!');
    }
}
