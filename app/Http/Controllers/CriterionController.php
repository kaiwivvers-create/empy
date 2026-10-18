<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CriterionController extends Controller
{
    public function index(Request $request): View
    {
        $criteria = Criterion::orderBy('sort_order')->orderBy('id')->paginate(15);

        [$openModal, $editItem] = $this->modalState($request);

        return view('criteria.index', [
            'criteria' => $criteria,
            'totalWeight' => Criterion::totalWeight(),
            'openModal' => $openModal,
            'editItem' => $editItem,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('criteria.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $criterion = Criterion::create($this->validated($request));

        ActivityLogger::record('created', 'Criterion created: '.$criterion->name, $criterion);

        return redirect()->route('criteria.index')->with('success', __('app.criteria.success_created'));
    }

    public function edit(Criterion $criterion): RedirectResponse
    {
        return redirect()->route('criteria.index', ['open' => 'edit-'.$criterion->id]);
    }

    public function update(Request $request, Criterion $criterion): RedirectResponse
    {
        $before = $criterion->getAttributes();

        $criterion->update($this->validated($request));

        ActivityLogger::record(
            'updated',
            'Criterion updated: '.$criterion->name,
            $criterion,
            ActivityLogger::changes($criterion, $before),
        );

        return redirect()->route('criteria.index')->with('success', __('app.criteria.success_updated'));
    }

    public function destroy(Criterion $criterion): RedirectResponse
    {
        if ($criterion->scores()->exists()) {
            return back()->with('error', __('app.criteria.delete_blocked'));
        }

        ActivityLogger::record('deleted', 'Criterion deleted: '.$criterion->name, $criterion);

        $criterion->delete();

        return redirect()->route('criteria.index')->with('success', __('app.criteria.success_deleted'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    /**
     * @return array{0: ?string, 1: ?Criterion}
     */
    private function modalState(Request $request): array
    {
        $open = $request->string('open')->toString();
        $editItem = null;

        if (preg_match('/^edit-(\d+)$/', $open, $matches)) {
            $editItem = Criterion::find($matches[1]);
            $open = $editItem ? 'criterion-edit-'.$editItem->id : null;
        } elseif ($open === 'create') {
            $open = 'criterion-create';
        }

        return [$open ?: null, $editItem];
    }
}
