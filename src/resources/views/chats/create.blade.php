<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Chat') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">

                {{-- Вибір типу чату --}}
                <h3 class="font-semibold text-lg mb-4">New Chat</h3>

                <div class="grid grid-cols-2 gap-6">

                    {{-- Приватний чат --}}
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <h4 class="font-semibold mb-2">Private Chat</h4>
                        <form action="{{ route('chats.create.private') }}" method="POST">
                            @csrf

                            <label class="block mb-2 text-sm text-gray-700">Select user</label>

                            <select name="user_id" class="w-full border rounded px-2 py-2">
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>

                            <button
                                class="mt-3 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                                Create Private Chat
                            </button>
                        </form>
                    </div>

                    {{-- Груповий чат --}}
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <h4 class="font-semibold mb-2">Group Chat</h4>

                        <form action="{{ route('chats.create.group') }}" method="POST">
                            @csrf

                            <label class="block mb-2 text-sm text-gray-700">Group name</label>
                            <input name="name" type="text"
                                   class="w-full border rounded px-2 py-2 mb-3"
                                   placeholder="Enter group name">

                            <label class="block mb-2 text-sm text-gray-700">Select users</label>
                            <div class="max-h-40 overflow-y-auto border rounded p-2 mb-3">
                                @foreach($users as $user)
                                    <label class="block">
                                        <input type="checkbox" name="users[]" value="{{ $user->id }}">
                                        <span class="ml-2">{{ $user->name }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <button
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                                Create Group Chat
                            </button>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>
