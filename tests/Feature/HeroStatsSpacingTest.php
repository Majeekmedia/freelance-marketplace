<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the hero stats spacing.
 *
 * The bug this covers: at <=430px the stylesheet set
 * `padding-right:0; margin-right:0` on `.stat` AND `gap:.85rem 0` on `.stats`,
 * which zeroes the column gap. The three uppercase labels then touched, reading
 * as one string: "IN ESCROW NOWJOBS FUNDEDMEDIAN RELEASE".
 *
 * A CSS layout bug cannot be caught by asserting rendered HTML — the markup was
 * always correct, the spacing was not. So these tests read the stylesheet and
 * assert the specific properties that caused it, which is the closest a
 * server-side test can get to "the labels are not touching".
 */
class HeroStatsSpacingTest extends TestCase
{
    use RefreshDatabase;

    private function css(): string
    {
        $view = file_get_contents(resource_path('views/welcome.blade.php'));

        // Only the style block matters; markup is checked separately.
        preg_match('#<style>(.*?)</style>#s', $view, $m);

        return $m[1] ?? '';
    }

    /** Return the body of a media query at a given max-width. */
    private function mediaBlock(string $maxWidth): string
    {
        $css = $this->css();

        $needle = '@media (max-width:'.$maxWidth.'px){';
        $start = strpos($css, $needle);

        if ($start === false) {
            return '';
        }

        // Walk braces so nested blocks do not end the match early.
        $depth = 0;
        $i = $start + strlen($needle) - 1;
        $len = strlen($css);

        for ($end = $i; $end < $len; $end++) {
            if ($css[$end] === '{') {
                $depth++;
            } elseif ($css[$end] === '}') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }

        return substr($css, $start, $end - $start + 1);
    }

    public function test_the_narrow_mobile_block_does_not_zero_the_column_gap(): void
    {
        $block = $this->mediaBlock('430');

        expect($block)->not->toBe('', 'The 430px media block is missing.');

        // The exact shape of the original bug: a two-value gap with 0 columns.
        expect($block)->not->toMatch('/\.stats\s*\{[^}]*gap:\s*[\d.]+rem\s+0\b/')
            ->and($block)->not->toMatch('/\.stats\s*\{[^}]*column-gap:\s*0\b/');
    }

    public function test_the_narrow_mobile_block_uses_a_grid_so_figures_are_not_crushed(): void
    {
        $block = $this->mediaBlock('430');

        // Three flex columns of ~105px cannot hold "MEDIAN RELEASE"; a two-column
        // grid gives each figure ~160px.
        expect($block)->toContain('grid-template-columns');
    }

    public function test_stats_have_real_spacing_at_every_breakpoint(): void
    {
        // Either a flex gap or a grid gap must be non-zero wherever `.stats`
        // is redefined. This is the general form of the bug.
        $css = $this->css();

        preg_match_all('/\.stats\s*\{([^}]*)\}/', $css, $matches);

        expect($matches[1])->not->toBeEmpty();

        foreach ($matches[1] as $declaration) {
            if (preg_match('/gap:\s*([^\s;]+)\s+([^\s;]+)/', $declaration, $gap)) {
                // A two-value gap whose column component is 0 is the defect.
                expect($gap[2])->not->toBe('0', 'A .stats rule sets column-gap to 0: '.$declaration);
            }
        }
    }

    public function test_the_stats_block_renders_all_three_figures(): void
    {
        $response = $this->get('/')->assertOk();

        // With the marketing copy restored by product decision, all three
        // figures and their labels must be present.
        $response->assertSee('In escrow now')
            ->assertSee('Jobs funded')
            ->assertSee('Median release');
    }

    public function test_the_stats_labels_are_separate_elements_not_one_string(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Each label lives in its own .stat-lab span, so the browser lays them
        // out independently. If the markup ever collapsed into one text node,
        // no amount of CSS could separate them.
        expect(substr_count($html, 'class="stat-lab"'))->toBe(3)
            ->and(substr_count($html, 'class="stat"'))->toBeGreaterThanOrEqual(3);
    }
}
