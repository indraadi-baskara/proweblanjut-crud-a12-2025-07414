<?php declare(strict_types=1) ?>
<?php if (!empty($items)): ?>
<div class="mb-6 flex items-start gap-3 bg-amber-950/60 border border-amber-800/60 text-amber-300 rounded-xl px-4 py-3 text-sm">
    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <span>
        <strong class="font-semibold text-amber-200">Low stock warning</strong>
        (threshold: ≤ <?= $threshold ?>) —
        <?= implode(
            ", ",
            array_map(
                fn($i) => htmlspecialchars($i->itemName, ENT_QUOTES, "UTF-8") .
                    " (" .
                    $i->quantity .
                    ")",
                $items,
            ),
        ) ?>
    </span>
</div>
<?php endif; ?>
