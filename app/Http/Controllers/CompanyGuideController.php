<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreGuideRequest;
use App\Http\Requests\UpdateGuideRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class CompanyGuideController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Company $company)
    {
        $this->authorize('viewAny', $company);
        
        $guides = $company->users()->where('role_id', Role::GUIDE->value)->get();

        return view('companies.guides.index', compact('company', 'guides'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Company $company)
    {
        $this->authorize('create', $company);

        return view('companies.guides.create', compact('company'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGuideRequest $request, Company $company)
    {
        $this->authorize('create', $company);
        
        $company->users()->create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'role_id' => Role::GUIDE->value,
        ]);

        return to_route('companies.guides.index', $company);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company, User $guide)
    {
        return view('companies.guides.show', compact('company', 'guide'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company, User $guide)
    {
        $this->authorize('update', $company);

        return view('companies.guides.edit', compact('company', 'guide'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGuideRequest $request, Company $company, User $guide)
    {
        $this->authorize('update', $company);

        $guide->update($request->validated());

        return to_route('companies.guides.index', $company);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company, User $guide)
    {
        $this->authorize('delete', $company);

        $guide->delete();

        return to_route('companies.guides.index', $company);
    }
}
