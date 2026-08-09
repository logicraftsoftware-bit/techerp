<?php

namespace App\Http\Controllers;

use App\Http\Requests\SkillRequest;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function index(Request $r): View
    {
        $records = Skill::withCount('technicians')->when($r->search, fn ($q, $s) => $q->where('name', 'like', "%$s%"))->latest()->paginate(15)->withQueryString();

        return view('master.skills.index', compact('records'));
    }

    public function create(): View
    {
        return view('master.skills.form', ['skill' => new Skill]);
    }

    public function store(SkillRequest $r): RedirectResponse
    {
        Skill::create($r->validated() + ['is_active' => $r->boolean('is_active')]);

        return to_route('skills.index')->with('success', 'Skill created.');
    }

    public function edit(Skill $skill): View
    {
        return view('master.skills.form', compact('skill'));
    }

    public function update(SkillRequest $r, Skill $skill): RedirectResponse
    {
        $skill->update($r->validated() + ['is_active' => $r->boolean('is_active')]);

        return to_route('skills.index')->with('success', 'Skill updated.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        abort_if($skill->technicians()->exists(), 422, 'This skill is assigned to technicians.');
        $skill->delete();

        return back()->with('success','Skill deleted.');
    }
}
