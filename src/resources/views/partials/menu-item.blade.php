@if(!empty($menu['children']))
    <div class="inline-block relative group">
        <a href="{{ $menu['route'] ?? '#' }}" class="hover:underline">{{ $menu['title'] }}</a>
        <div class="absolute min-w-[150px] left-0 top-5 p-3 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded mt-1 z-50 space-y-3">
            @foreach($menu['children'] as $child)
                @include('partials.menu-item', ['menu' => $child])
            @endforeach
        </div>
    </div>
@else
    <a href="{{ $menu['route'] ?? '#' }}" class="hover:font-bold block">{{ $menu['title'] }}</a>
@endif
