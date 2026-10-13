{{-- Permissions table: a row per module, a column per action. Tailwind + Alpine only. --}}
@php
    use Illuminate\Support\Js;

    $actions = $getActionColumns();
    $groups = $getGroups();
    $editable = collect($groups)->pluck('modules')->collapse()->mapWithKeys(fn ($module) => [$module['key'] => $module['editable']]);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            editable: @js($editable),
            has(module, action) {
                return (this.state?.[module] ?? []).includes(action)
            },
            canEdit(module, action) {
                return (this.editable[module] ?? []).includes(action)
            },
            set(module, action, on) {
                if (! this.canEdit(module, action)) return
                const current = (this.state?.[module] ?? []).filter((value) => value !== action)
                this.state = { ...this.state, [module]: on ? [...current, action] : current }
            },
            rowIsFull(module) {
                return (this.editable[module] ?? []).length > 0
                    && this.editable[module].every((action) => this.has(module, action))
            },
            setRow(module, on) {
                this.editable[module]?.forEach((action) => this.set(module, action, on))
            },
            groupIsFull(modules) {
                const rows = modules.filter((module) => (this.editable[module] ?? []).length > 0)
                return rows.length > 0 && rows.every((module) => this.rowIsFull(module))
            },
            setGroup(modules, on) {
                modules.forEach((module) => this.setRow(module, on))
            },
            setAll(on) {
                Object.keys(this.editable).forEach((module) => this.setRow(module, on))
            },
        }"
        class="space-y-3"
    >
        @unless ($isDisabled())
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <button type="button" x-on:click="setAll(true)" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">
                    {{ __('admin.roles.select_all') }}
                </button>
                <button type="button" x-on:click="setAll(false)" class="font-semibold text-gray-500 hover:underline dark:text-gray-400">
                    {{ __('admin.roles.clear_all') }}
                </button>
            </div>
        @endunless

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full min-w-[36rem] text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('admin.roles.module') }}</th>
                        @foreach ($actions as $action)
                            <th class="px-3 py-3 text-center font-semibold text-gray-950 dark:text-white">{{ $action['label'] }}</th>
                        @endforeach
                        <th class="px-3 py-3 text-center font-semibold text-gray-950 dark:text-white">{{ __('admin.roles.all') }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($groups as $group)
                        @php($groupModules = Js::from(array_column($group['modules'], 'key')))
                        <tr class="bg-gray-50/60 dark:bg-white/[0.02]">
                            <td colspan="{{ count($actions) + 1 }}" class="px-4 py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                {{ $group['label'] }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                {{-- Select all in this group --}}
                                <x-filament::input.checkbox
                                    :aria-label="$group['label'].' - '.__('admin.roles.all')"
                                    x-bind:checked="groupIsFull({{ $groupModules }})"
                                    x-bind:disabled="! {{ $groupModules }}.some((module) => (editable[module] ?? []).length > 0)"
                                    x-on:change="setGroup({{ $groupModules }}, $event.target.checked)"
                                />
                            </td>
                        </tr>

                        @foreach ($group['modules'] as $module)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5" wire:key="perm-row-{{ $module['key'] }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $module['label'] }}</div>
                                    @if ($module['locked'])
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.roles.locked_for_admin') }}</div>
                                    @endif
                                </td>

                                @foreach ($actions as $action)
                                    <td class="px-3 py-3 text-center">
                                        @if (in_array($action['value'], $module['offered'], true))
                                            <x-filament::input.checkbox
                                                :aria-label="$module['label'].' - '.$action['label']"
                                                x-bind:checked="has({{ Js::from($module['key']) }}, {{ Js::from($action['value']) }})"
                                                x-bind:disabled="! canEdit({{ Js::from($module['key']) }}, {{ Js::from($action['value']) }})"
                                                x-on:change="set({{ Js::from($module['key']) }}, {{ Js::from($action['value']) }}, $event.target.checked)"
                                            />
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-3 py-3 text-center">
                                    <x-filament::input.checkbox
                                        :aria-label="$module['label'].' - '.__('admin.roles.all')"
                                        x-bind:checked="rowIsFull({{ Js::from($module['key']) }})"
                                        x-bind:disabled="(editable[{{ Js::from($module['key']) }}] ?? []).length === 0"
                                        x-on:change="setRow({{ Js::from($module['key']) }}, $event.target.checked)"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-dynamic-component>
