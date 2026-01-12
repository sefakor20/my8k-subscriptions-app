<flux:modal wire:model.self="show" @close="closeModal" class="md:w-[600px]">
    <form wire:submit="submit">
        <div class="p-6">
            {{-- Header --}}
            <div class="mb-6">
                <flux:heading size="lg">Create Support Ticket</flux:heading>
                <flux:text variant="muted" class="mt-1">
                    Describe your issue and we'll get back to you as soon as possible
                </flux:text>
            </div>

            {{-- Form Fields --}}
            <div class="space-y-6">
                {{-- Subject --}}
                <flux:field>
                    <flux:label>Subject</flux:label>
                    <flux:input
                        wire:model="subject"
                        placeholder="Brief description of your issue"
                        required
                    />
                    <flux:error name="subject" />
                </flux:field>

                {{-- Category --}}
                <flux:field>
                    <flux:label>Category</flux:label>
                    <flux:select wire:model="category" required>
                        <option value="">Select a category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}">
                                {{ $cat->label() }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="category" />
                </flux:field>

                {{-- Priority --}}
                <flux:field>
                    <flux:label>Priority</flux:label>
                    <flux:select wire:model="priority" required>
                        @foreach($priorities as $prio)
                            <option value="{{ $prio->value }}">
                                {{ $prio->label() }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="priority" />
                </flux:field>

                {{-- Related Subscription (Optional) --}}
                @if($subscriptions->isNotEmpty())
                    <flux:field>
                        <flux:label>Related Subscription (Optional)</flux:label>
                        <flux:select wire:model="subscription_id">
                            <option value="">No subscription selected</option>
                            @foreach($subscriptions as $subscription)
                                <option value="{{ $subscription->id }}">
                                    {{ $subscription->product_name }} - {{ $subscription->domain }}
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="subscription_id" />
                    </flux:field>
                @endif

                {{-- Related Order (Optional) --}}
                @if($orders->isNotEmpty())
                    <flux:field>
                        <flux:label>Related Order (Optional)</flux:label>
                        <flux:select wire:model="order_id">
                            <option value="">No order selected</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->id }}">
                                    Order #{{ substr($order->id, 0, 8) }} - {{ $order->created_at->format('M d, Y') }}
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="order_id" />
                    </flux:field>
                @endif

                {{-- Message --}}
                <flux:field>
                    <flux:label>Message</flux:label>
                    <flux:textarea
                        wire:model="message"
                        placeholder="Please provide detailed information about your issue..."
                        rows="6"
                        required
                    />
                    <flux:error name="message" />
                    <flux:text variant="muted" size="sm">
                        Minimum 10 characters
                    </flux:text>
                </flux:field>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="close">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create Ticket</flux:button>
            </div>
        </div>
    </form>
</flux:modal>
