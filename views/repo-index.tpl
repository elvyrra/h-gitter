<div id="h-gitter-repository-index">
    <nav class="navbar navbar-default">
        <div class="container">
            <ul class="nav navbar-nav">
                {foreach($menuItems as $name => $item)}
                    <li {if($active === $name)}class="active"{/if}>
                        <a href="{{ $item['url'] }}" target="{{ empty($item['target']) ? '': $item['target'] }}">
                            {icon icon="{$item['icon']}"}
                            {text key="{'h-gitter.repo-menu-item-' . $name}"}
                            {if(!empty($item['number']))}
                                <span class="badge bg-info">{{ $item['number'] }}</span>
                            {/if}
                        </a>
                    </li>
                {/foreach}
            </ul>
        </div>
    </nav>

    <div id="h-gitter-repo-content">
        {{ $home }}
    </div>
</div>
