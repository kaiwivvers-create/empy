<div class="overflow-x-auto">
    <table class="table-stable w-full {{ $canManage ? 'min-w-[44rem]' : 'min-w-[36rem]' }}">
        <colgroup>
            <col>
            <col class="w-[11rem]">
            <col class="w-[11rem]">
            @if ($canManage)
                <col class="w-[8rem]">
            @endif
        </colgroup>
        <thead class="table-head">
            <tr>
                <th>{{ __('app.common.name') }}</th>
                <th>{{ __('app.goals.period') }}</th>
                <th class="text-center">{{ __('app.goals.progress') }}</th>
                @if ($canManage)
                    <th class="text-right">{{ __('app.common.actions') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($goals as $goal)
                @php $percent = $goal->achievementPercent(); @endphp
                <tr class="table-row">
                    <td>
                        <p class="font-semibold text-slate-800">{{ $goal->title }}</p>
                        <p class="text-xs text-slate-400">
                            {{ __('app.goals.target') }}: {{ rtrim(rtrim(number_format((float) $goal->target_value, 2), '0'), '.') }} {{ $goal->unit }}
                            @if ($goal->notes) · {{ $goal->notes }} @endif
                        </p>
                    </td>
                    <td class="text-slate-500">{{ $goal->period?->name }}</td>
                    <td class="text-center">
                        @if ($percent === null)
                            <span class="badge-slate">{{ __('app.common.not_yet') }}</span>
                        @else
                            <div class="mx-auto w-32">
                                <p class="text-sm font-bold {{ $percent >= 100 ? 'text-emerald-600' : ($percent >= 75 ? 'text-indigo-600' : 'text-amber-600') }}">
                                    {{ number_format($percent, 1) }}%
                                </p>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ min($percent, 100) }}%"></div>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-400">
                                    {{ rtrim(rtrim(number_format((float) $goal->achievement_value, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format((float) $goal->target_value, 2), '0'), '.') }} {{ $goal->unit }}
                                </p>
                            </div>
                        @endif
                    </td>
                    @if ($canManage)
                        <td>
                            <form method="POST" action="{{ route('employees.goals.destroy', [$employee, $goal]) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')" class="flex items-center justify-end">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                </button>
                            </form>
                        </td>
                    @endif
                </tr>
                @if ($canManage)
                    <tr class="border-t border-slate-50 bg-slate-50/50">
                        <td colspan="{{ $canManage ? 4 : 3 }}" class="px-5 py-3">
                            <form method="POST" action="{{ route('employees.goals.update', [$employee, $goal]) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="period_id" value="{{ $goal->period_id }}">
                                <input type="hidden" name="title" value="{{ $goal->title }}">
                                <input type="hidden" name="target_value" value="{{ $goal->target_value }}">
                                <input type="hidden" name="unit" value="{{ $goal->unit }}">
                                <div>
                                    <label class="label !mb-1 text-xs">{{ __('app.goals.achievement') }}</label>
                                    <input type="number" step="0.01" min="0" name="achievement_value" value="{{ $goal->achievement_value }}" class="input !py-1.5 w-28 text-xs">
                                </div>
                                <div class="flex-1">
                                    <label class="label !mb-1 text-xs">{{ __('app.goals.notes') }}</label>
                                    <input type="text" name="notes" value="{{ $goal->notes }}" class="input !py-1.5 text-xs">
                                </div>
                                <button type="submit" class="btn-secondary btn-sm">{{ __('app.actions.save') }}</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="{{ $canManage ? 4 : 3 }}" class="empty-state">{{ __('app.goals.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
