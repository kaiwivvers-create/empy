<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $positions = Position::withCount('employees')->orderBy('name')->paginate(10);

        [$openModal, $editItem] = $this->modalState($request);

        return view('positions.index', compact('positions', 'openModal', 'editItem'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('positions.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $position = Position::create($this->validated($request));

        ActivityLogger::record('created', 'Position created: '.$position->name, $position);

        return redirect()->route('positions.index')->with('success', __('app.positions.success_created'));
    }

    public function edit(Position $position): RedirectResponse
    {
        return redirect()->route('positions.index', ['open' => 'edit-'.$position->id]);
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $before = $position->getAttributes();

        $position->update($this->validated($request));

        ActivityLogger::record(
            'updated',
            'Position updated: '.$position->name,
            $position,
            ActivityLogger::changes($position, $before),
        );

        return redirect()->route('positions.index')->with('success', __('app.positions.success_updated'));
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->employees()->exists()) {
            return back()->with('error', __('app.positions.delete_blocked'));
        }

        ActivityLogger::record('deleted', 'Position deleted: '.$position->name, $position);

        $position->delete();

        return redirect()->route('positions.index')->with('success', __('app.positions.success_deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:50'],
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?Position}
     */
    private function modalState(Request $request): array
    {
        $open = $request->string('open')->toString();
        $editItem = null;

        if (preg_match('/^edit-(\d+)$/', $open, $matches)) {
            $editItem = Position::find($matches[1]);
            $open = $editItem ? 'position-edit-'.$editItem->id : null;
        } elseif ($open === 'create') {
            $open = 'position-create';
        }

        return [$open ?: null, $editItem];
    }
}
