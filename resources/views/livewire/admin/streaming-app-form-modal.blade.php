<flux:modal wire:model.self="show" @close="closeModal" class="max-w-2xl">
    <div class="p-6">
        {{-- Header --}}
        <div class="mb-6">
            <flux:heading size="lg">{{ $mode === 'create' ? 'Add Streaming App' : 'Edit Streaming App' }}</flux:heading>
            <flux:text variant="muted" class="mt-1">
                {{ $mode === 'create' ? 'Add a new streaming app for customers to download' : 'Update streaming app details' }}
            </flux:text>
        </div>

        {{-- Form --}}
        <form wire:submit="save">
            <div class="space-y-6">
                {{-- Basic Information --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Basic Information</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>App Name *</flux:label>
                                <flux:input wire:model="name" type="text" placeholder="e.g., Smart STB Player" />
                                <flux:error name="name" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Version</flux:label>
                                <flux:input wire:model="version" type="text" placeholder="e.g., v2.1.0" />
                                <flux:error name="version" />
                            </flux:field>
                        </div>

                        <div class="md:col-span-2">
                            <flux:field>
                                <flux:label>Description</flux:label>
                                <flux:textarea wire:model="description" rows="2" placeholder="Brief description of the app" />
                                <flux:error name="description" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Platform & Type --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Platform & Type</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Platform *</flux:label>
                                <flux:select wire:model="platform">
                                    @foreach ($platforms as $p)
                                        <option value="{{ $p['value'] }}">{{ $p['label'] }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="platform" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Type *</flux:label>
                                <flux:select wire:model="type">
                                    @foreach ($types as $t)
                                        <option value="{{ $t['value'] }}">{{ $t['label'] }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="type" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Download Information --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Download Information</flux:heading>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Download URL *</flux:label>
                                <flux:input wire:model="download_url" type="url" placeholder="https://example.com/app.apk" />
                                <flux:error name="download_url" />
                            </flux:field>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <flux:field>
                                    <flux:label>Downloader Code</flux:label>
                                    <flux:input wire:model="downloader_code" type="text" placeholder="e.g., 123456" />
                                    <flux:error name="downloader_code" />
                                    <flux:text variant="muted" class="text-xs mt-1">
                                        Code for Code Downloader apps
                                    </flux:text>
                                </flux:field>
                            </div>

                            <div>
                                <flux:field>
                                    <flux:label>Short URL</flux:label>
                                    <flux:input wire:model="short_url" type="text" placeholder="e.g., 2u.pw/app" />
                                    <flux:error name="short_url" />
                                    <flux:text variant="muted" class="text-xs mt-1">
                                        Easy-to-type short URL
                                    </flux:text>
                                </flux:field>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Display Settings --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Display Settings</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Sort Order</flux:label>
                                <flux:input wire:model="sort_order" type="number" min="0" placeholder="0" />
                                <flux:error name="sort_order" />
                                <flux:text variant="muted" class="text-xs mt-1">
                                    Lower numbers appear first
                                </flux:text>
                            </flux:field>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <flux:field>
                            <div class="flex items-center gap-3">
                                <flux:switch wire:model="is_recommended" />
                                <flux:label>Recommended App</flux:label>
                            </div>
                            <flux:text variant="muted" class="text-xs mt-1">
                                Highlight this app as recommended to customers
                            </flux:text>
                        </flux:field>

                        <flux:field>
                            <div class="flex items-center gap-3">
                                <flux:switch wire:model="is_active" />
                                <flux:label>Active</flux:label>
                            </div>
                            <flux:text variant="muted" class="text-xs mt-1">
                                Only active apps are visible to customers
                            </flux:text>
                        </flux:field>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="closeModal" type="button" variant="ghost">
                    Cancel
                </flux:button>

                <flux:button type="submit" variant="primary">
                    {{ $mode === 'create' ? 'Add App' : 'Update App' }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
