<?php
/** @var array $items */
$user = auth()->user();
if ($user === null) {
    return;
}

$pluginHref = static function (string $alias): string {
    try {
        $url = route_to($alias);

        return is_string($url) && $url !== '' ? $url : '#';
    } catch (\Throwable) {
        return '#';
    }
};
?>
<?php foreach ($items as $item): ?>
    <?php if (empty($item['permission']) || ! $user->can($item['permission'])) {
        continue;
    } ?>

    <?php
    $children = [];
    if (! empty($item['children']) && is_array($item['children'])) {
        $children = array_values(array_filter(
            $item['children'],
            static fn ($child) => is_array($child)
                && ! empty($child['permission'])
                && $user->can($child['permission'])
        ));
        if ($children === []) {
            continue;
        }
    }
    ?>

    <?php if (! empty($item['section'])): ?>
        <li class="sidebar-header"><?= esc($item['section']) ?></li>
    <?php endif; ?>

    <?php
    $slug     = (string) ($item['slug'] ?? '');
    $id       = 'plg-nav-' . preg_replace('/[^a-z0-9-]/', '', $slug);
    if ($id === 'plg-nav-') {
        $id = 'plg-nav-' . substr(md5((string) ($item['route'] ?? $item['label'] ?? 'plugin')), 0, 8);
    }
    $isActive = ! empty($item['route_pattern']) && url_is($item['route_pattern']);
    $icon     = (string) ($item['icon'] ?? 'fa-plug');
    if (preg_match('/^fa-[a-z0-9-]+$/', $icon) !== 1) {
        $icon = 'fa-plug';
    }
    ?>

    <?php if ($children !== []): ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a data-bs-target="#<?= esc($id, 'attr') ?>" data-bs-toggle="collapse"
               class="sidebar-link <?= $isActive ? '' : 'collapsed' ?>">
                <i class="fas fa-fw <?= esc($icon, 'attr') ?> align-middle me-1"></i>
                <span class="align-middle"><?= esc($item['label'] ?? 'Plugin') ?></span>
            </a>
            <ul id="<?= esc($id, 'attr') ?>" class="sidebar-dropdown list-unstyled collapse <?= $isActive ? 'show' : '' ?>"
                data-bs-parent="#sidebar">
                <?php foreach ($children as $child): ?>
                    <?php $childActive = ! empty($child['route_pattern']) && url_is($child['route_pattern']); ?>
                    <li class="sidebar-item <?= $childActive ? 'active' : '' ?>">
                        <a class="sidebar-link" href="<?= esc($pluginHref((string) ($child['route'] ?? '')), 'attr') ?>"><?= esc($child['label'] ?? '') ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php else: ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a class="sidebar-link" href="<?= esc($pluginHref((string) ($item['route'] ?? '')), 'attr') ?>">
                <i class="fas fa-fw <?= esc($icon, 'attr') ?> align-middle me-1"></i>
                <span class="align-middle"><?= esc($item['label'] ?? 'Plugin') ?></span>
            </a>
        </li>
    <?php endif; ?>
<?php endforeach; ?>
