<?php
/**
 * Visual control tree: Global → Page → Section → Component → Element.
 *
 * Each node stores own overrides per breakpoint. Inheritance is resolved
 * at read time; the compiler emits only a node's own declarations.
 *
 * @package MahmoudElsaad\Core
 */

declare(strict_types=1);

namespace MahmoudElsaad\Core\Visual;

final class Tree
{
    public const OPTION = 'mes_visual_tree';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'id'       => 'global',
            'type'     => 'global',
            'parent'   => '',
            'label'    => 'Global',
            'selector' => ':root',
            'props'    => self::empty_props(),
            'children' => [
                self::page('home', 'Homepage', [
                    self::section('home.hero', 'Hero', [
                        self::component('home.hero.copy', 'Hero copy', [
                            self::element('home.hero.title', 'Heading'),
                            self::element('home.hero.lead', 'Lead'),
                            self::element('home.hero.cta', 'Primary CTA'),
                        ]),
                        self::component('home.hero.media', 'Hero media', [
                            self::element('home.hero.image', 'Image'),
                        ]),
                    ]),
                    self::section('home.services', 'Services', [
                        self::component('home.services.grid', 'Service grid', [
                            self::element('home.services.heading', 'Heading'),
                            self::element('home.services.card', 'Card'),
                        ]),
                    ]),
                    self::section('home.cities', 'Cities', [
                        self::component('home.cities.grid', 'City grid', [
                            self::element('home.cities.heading', 'Heading'),
                            self::element('home.cities.card', 'Card'),
                        ]),
                    ]),
                    self::section('home.offers', 'Offers', [
                        self::component('home.offers.list', 'Offer list', [
                            self::element('home.offers.heading', 'Heading'),
                            self::element('home.offers.card', 'Card'),
                        ]),
                    ]),
                    self::section('home.reviews', 'Reviews', [
                        self::component('home.reviews.list', 'Review list', [
                            self::element('home.reviews.heading', 'Heading'),
                            self::element('home.reviews.card', 'Card'),
                        ]),
                    ]),
                    self::section('home.team', 'Team', [
                        self::component('home.team.grid', 'Team grid', [
                            self::element('home.team.heading', 'Heading'),
                            self::element('home.team.card', 'Card'),
                        ]),
                    ]),
                    self::section('home.partners', 'Partners', [
                        self::component('home.partners.row', 'Partner row', [
                            self::element('home.partners.heading', 'Heading'),
                            self::element('home.partners.logo', 'Logo'),
                        ]),
                    ]),
                    self::section('home.faq', 'FAQ', [
                        self::component('home.faq.list', 'FAQ list', [
                            self::element('home.faq.heading', 'Heading'),
                            self::element('home.faq.item', 'Item'),
                        ]),
                    ]),
                    self::section('home.cta', 'Closing CTA', [
                        self::component('home.cta.box', 'CTA box', [
                            self::element('home.cta.heading', 'Heading'),
                            self::element('home.cta.button', 'Button'),
                        ]),
                    ]),
                    self::section('home.trust', 'Trust', [
                        self::component('home.trust.row', 'Trust row', [
                            self::element('home.trust.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.kpis', 'KPIs', [
                        self::component('home.kpis.row', 'KPI row', [
                            self::element('home.kpis.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.finder', 'Finder', [
                        self::component('home.finder.box', 'Finder box', [
                            self::element('home.finder.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.why', 'Why us', [
                        self::component('home.why.copy', 'Copy', [
                            self::element('home.why.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.stats', 'Stats', [
                        self::component('home.stats.row', 'Stats row', [
                            self::element('home.stats.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.comparison', 'Comparison', [
                        self::component('home.comparison.table', 'Table', [
                            self::element('home.comparison.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.before_after', 'Before / After', [
                        self::component('home.before_after.grid', 'Grid', [
                            self::element('home.before_after.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.portfolio', 'Portfolio', [
                        self::component('home.portfolio.grid', 'Grid', [
                            self::element('home.portfolio.heading', 'Heading'),
                            self::element('home.portfolio.card', 'Card'),
                        ]),
                    ]),
                    self::section('home.blog', 'Blog', [
                        self::component('home.blog.grid', 'Grid', [
                            self::element('home.blog.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.case_studies', 'Case studies', [
                        self::component('home.case_studies.grid', 'Grid', [
                            self::element('home.case_studies.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.certs', 'Certificates', [
                        self::component('home.certs.row', 'Row', [
                            self::element('home.certs.heading', 'Heading'),
                        ]),
                    ]),
                    self::section('home.pricing', 'Pricing', [
                        self::component('home.pricing.list', 'List', [
                            self::element('home.pricing.heading', 'Heading'),
                            self::element('home.pricing.card', 'Card'),
                        ]),
                    ]),
                    self::section('home.knowledge', 'Knowledge', [
                        self::component('home.knowledge.list', 'List', [
                            self::element('home.knowledge.heading', 'Heading'),
                        ]),
                    ]),
                ]),
                self::page('archive-service', 'Services archive', [
                    self::section('archive-service.hero', 'Archive hero', [
                        self::component('archive-service.hero.copy', 'Copy', [
                            self::element('archive-service.hero.title', 'Heading'),
                        ]),
                    ]),
                ]),
                self::page('single-service', 'Service page', [
                    self::section('single-service.hero', 'Service hero', [
                        self::component('single-service.hero.copy', 'Copy', [
                            self::element('single-service.hero.title', 'Heading'),
                        ]),
                    ]),
                ]),
                self::page('archive-city', 'Cities archive', [
                    self::section('archive-city.hero', 'Archive hero', [
                        self::component('archive-city.hero.copy', 'Copy', [
                            self::element('archive-city.hero.title', 'Heading'),
                        ]),
                    ]),
                ]),
                self::page('service-city', 'Service × City', [
                    self::section('service-city.hero', 'Landing hero', [
                        self::component('service-city.hero.copy', 'Copy', [
                            self::element('service-city.hero.title', 'Heading'),
                        ]),
                    ]),
                ]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $stored = get_option(self::OPTION, null);
        if (! is_array($stored) || empty($stored['id'])) {
            $tree = self::defaults();
            update_option(self::OPTION, $tree, false);
            return $tree;
        }
        return self::merge_missing($stored, self::defaults());
    }

    /**
     * @param array<string, mixed> $tree
     * @return array<string, mixed>
     */
    public static function save(array $tree): array
    {
        $tree = self::sanitize_tree($tree);
        update_option(self::OPTION, $tree, false);
        Compiler::persist($tree);
        return $tree;
    }

    /**
     * Resolve inherited + own props for a node at a breakpoint.
     *
     * @return array<string, string>
     */
    public static function resolve(string $node_id, string $breakpoint = 'desktop'): array
    {
        $path = self::path($node_id);
        $merged = [];
        foreach ($path as $node) {
            $own = self::own_props($node, $breakpoint);
            $merged = array_merge($merged, $own);
        }
        return $merged;
    }

    /**
     * Own (non-inherited) props for a node at a breakpoint, with cascade
     * Desktop → Tablet → Mobile for that node only.
     *
     * @param array<string, mixed> $node
     * @return array<string, string>
     */
    public static function own_props(array $node, string $breakpoint): array
    {
        $props = $node['props'] ?? [];
        $desktop = Schema::sanitize_props($props['desktop'] ?? []);
        if ($breakpoint === 'desktop') {
            return $desktop;
        }
        $tablet = Schema::sanitize_props($props['tablet'] ?? []);
        if ($breakpoint === 'tablet') {
            return array_merge($desktop, $tablet);
        }
        $mobile = Schema::sanitize_props($props['mobile'] ?? []);
        return array_merge($desktop, $tablet, $mobile);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function path(string $node_id): array
    {
        $found = [];
        $walk = static function (array $node, array $stack) use (&$walk, &$found, $node_id): void {
            $next = array_merge($stack, [$node]);
            if (($node['id'] ?? '') === $node_id) {
                $found = $next;
                return;
            }
            foreach ($node['children'] ?? [] as $child) {
                if (is_array($child) && $found === []) {
                    $walk($child, $next);
                }
            }
        };
        $walk(self::get(), []);
        return $found;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $node_id, ?array $tree = null): ?array
    {
        $tree = $tree ?? self::get();
        if (($tree['id'] ?? '') === $node_id) {
            return $tree;
        }
        foreach ($tree['children'] ?? [] as $child) {
            if (! is_array($child)) {
                continue;
            }
            $hit = self::find($node_id, $child);
            if ($hit) {
                return $hit;
            }
        }
        return null;
    }

    /**
     * Flatten for the editor tree UI.
     *
     * @return list<array<string, mixed>>
     */
    public static function flatten(?array $tree = null, int $depth = 0): array
    {
        $tree = $tree ?? self::get();
        $rows = [[
            'id'     => (string) ($tree['id'] ?? ''),
            'type'   => (string) ($tree['type'] ?? ''),
            'label'  => (string) ($tree['label'] ?? ''),
            'parent' => (string) ($tree['parent'] ?? ''),
            'depth'  => $depth,
            'props'  => $tree['props'] ?? self::empty_props(),
        ]];
        foreach ($tree['children'] ?? [] as $child) {
            if (is_array($child)) {
                $rows = array_merge($rows, self::flatten($child, $depth + 1));
            }
        }
        return $rows;
    }

    /**
     * @return array{desktop: array<string, string>, tablet: array<string, string>, mobile: array<string, string>}
     */
    public static function empty_props(): array
    {
        return [
            'desktop' => [],
            'tablet'  => [],
            'mobile'  => [],
        ];
    }

    /**
     * @param array<string, mixed> $stored
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    private static function merge_missing(array $stored, array $defaults): array
    {
        $out = $defaults;
        $out['props'] = self::merge_props($defaults['props'] ?? self::empty_props(), $stored['props'] ?? []);
        $out['label'] = (string) ($stored['label'] ?? $defaults['label']);
        $by_id = [];
        foreach ($stored['children'] ?? [] as $child) {
            if (is_array($child) && ! empty($child['id'])) {
                $by_id[(string) $child['id']] = $child;
            }
        }
        $children = [];
        foreach ($defaults['children'] ?? [] as $def) {
            $id = (string) ($def['id'] ?? '');
            if (isset($by_id[$id])) {
                $children[] = self::merge_missing($by_id[$id], $def);
                unset($by_id[$id]);
            } else {
                $children[] = $def;
            }
        }
        foreach ($by_id as $extra) {
            $children[] = self::sanitize_tree($extra);
        }
        $out['children'] = $children;
        return $out;
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private static function sanitize_tree(array $node): array
    {
        $id = sanitize_text_field((string) ($node['id'] ?? 'global'));
        $type = sanitize_key((string) ($node['type'] ?? 'element'));
        $children = [];
        foreach ($node['children'] ?? [] as $child) {
            if (is_array($child)) {
                $children[] = self::sanitize_tree($child);
            }
        }
        return [
            'id'       => $id,
            'type'     => $type,
            'parent'   => sanitize_text_field((string) ($node['parent'] ?? '')),
            'label'    => sanitize_text_field((string) ($node['label'] ?? $id)),
            'selector' => sanitize_text_field((string) ($node['selector'] ?? '')),
            'props'    => self::merge_props(self::empty_props(), $node['props'] ?? []),
            'children' => $children,
        ];
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $incoming
     * @return array{desktop: array<string, string>, tablet: array<string, string>, mobile: array<string, string>}
     */
    private static function merge_props(array $base, array $incoming): array
    {
        $out = self::empty_props();
        foreach (['desktop', 'tablet', 'mobile'] as $bp) {
            $out[$bp] = Schema::sanitize_props(array_merge(
                is_array($base[$bp] ?? null) ? $base[$bp] : [],
                is_array($incoming[$bp] ?? null) ? $incoming[$bp] : []
            ));
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private static function page(string $slug, string $label, array $children): array
    {
        return self::node('page:' . $slug, 'page', 'global', $label, $children);
    }

    /**
     * @param list<array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private static function section(string $id, string $label, array $children): array
    {
        $parent = 'page:' . explode('.', $id, 2)[0];
        return self::node('section:' . $id, 'section', $parent, $label, $children);
    }

    /**
     * @param list<array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private static function component(string $id, string $label, array $children): array
    {
        $parts = explode('.', $id);
        $parent = 'section:' . $parts[0] . '.' . ($parts[1] ?? '');
        return self::node('component:' . $id, 'component', $parent, $label, $children);
    }

    /**
     * @return array<string, mixed>
     */
    private static function element(string $id, string $label): array
    {
        $parts = explode('.', $id);
        $parent = 'component:' . $parts[0] . '.' . ($parts[1] ?? '') . '.' . ($parts[2] ?? '');
        return self::node('element:' . $id, 'element', $parent, $label, []);
    }

    /**
     * @param list<array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private static function node(string $id, string $type, string $parent, string $label, array $children): array
    {
        return [
            'id'       => $id,
            'type'     => $type,
            'parent'   => $parent,
            'label'    => $label,
            'selector' => '',
            'props'    => self::empty_props(),
            'children' => $children,
        ];
    }
}
