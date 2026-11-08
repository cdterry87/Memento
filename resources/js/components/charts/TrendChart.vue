<script setup lang="ts">
/**
 * The average mood score for each month, as a soft area chart from 1 to 5.
 * A month with no entries leaves a gap. The same figures are in a visually
 * hidden table for screen readers.
 */
import { monthName } from '@/composables/useLocalDate';
import type { MonthAverage } from '@/types';
import { computed, useId } from 'vue';

const props = defineProps<{
    months: MonthAverage[];
    title: string;
}>();

const WIDTH = 600;
const HEIGHT = 240;
const PADDING = { top: 16, right: 16, bottom: 32, left: 32 };
const MIN_SCORE = 1;
const MAX_SCORE = 5;

const id = useId();
const plotBottom = HEIGHT - PADDING.bottom;

function x(month: number): number {
    return PADDING.left + ((month - 1) / 11) * (WIDTH - PADDING.left - PADDING.right);
}

function y(score: number): number {
    const share = (score - MIN_SCORE) / (MAX_SCORE - MIN_SCORE);

    return plotBottom - share * (plotBottom - PADDING.top);
}

/** Runs of consecutive months that have an average, drawn as separate shapes. */
const runs = computed(() => {
    const result: { month: number; average: number }[][] = [];
    let current: { month: number; average: number }[] = [];

    for (const month of props.months) {
        if (month.average === null) {
            if (current.length) {
                result.push(current);
            }
            current = [];
        } else {
            current.push({ month: month.month, average: month.average });
        }
    }

    if (current.length) {
        result.push(current);
    }

    return result;
});

function linePath(run: { month: number; average: number }[]): string {
    return run
        .map((point, index) => `${index ? 'L' : 'M'}${x(point.month)},${y(point.average)}`)
        .join(' ');
}

function areaPath(run: { month: number; average: number }[]): string {
    const first = run[0];
    const last = run[run.length - 1];

    return `${linePath(run)} L${x(last.month)},${plotBottom} L${x(first.month)},${plotBottom} Z`;
}

const points = computed(() => runs.value.flat());
</script>

<template>
    <figure class="trend-chart">
        <svg
            :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
            role="img"
            :aria-labelledby="`${id}-title`"
            class="trend-chart__svg"
        >
            <title :id="`${id}-title`">{{ title }}</title>
            <defs>
                <linearGradient :id="`${id}-fill`" x1="0" y1="0" x2="0" y2="1">
                    <stop
                        offset="0%"
                        stop-color="rgb(var(--v-theme-primary))"
                        stop-opacity="0.35"
                    />
                    <stop offset="100%" stop-color="rgb(var(--v-theme-primary))" stop-opacity="0" />
                </linearGradient>
            </defs>

            <g class="trend-chart__grid">
                <template v-for="score in [1, 2, 3, 4, 5]" :key="score">
                    <line
                        :x1="PADDING.left"
                        :x2="WIDTH - PADDING.right"
                        :y1="y(score)"
                        :y2="y(score)"
                    />
                    <text
                        :x="PADDING.left - 10"
                        :y="y(score)"
                        text-anchor="end"
                        dominant-baseline="middle"
                    >
                        {{ score }}
                    </text>
                </template>
                <text
                    v-for="month in 12"
                    :key="month"
                    :x="x(month)"
                    :y="HEIGHT - 8"
                    text-anchor="middle"
                >
                    {{ monthName(month, { short: true }).slice(0, 1) }}
                </text>
            </g>

            <g v-for="(run, index) in runs" :key="index">
                <path :d="areaPath(run)" :fill="`url(#${id}-fill)`" />
                <path :d="linePath(run)" class="trend-chart__line" />
            </g>

            <circle
                v-for="point in points"
                :key="point.month"
                :cx="x(point.month)"
                :cy="y(point.average)"
                r="5"
                class="trend-chart__point"
            />
        </svg>

        <table class="visually-hidden">
            <caption>
                {{
                    title
                }}
            </caption>
            <thead>
                <tr>
                    <th scope="col">Month</th>
                    <th scope="col">Average mood, out of 5</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="month in months" :key="month.month">
                    <th scope="row">{{ monthName(month.month) }}</th>
                    <td>{{ month.average ?? 'No entries' }}</td>
                </tr>
            </tbody>
        </table>
    </figure>
</template>

<style scoped>
.trend-chart {
    position: relative;
    margin: 0;
}

.trend-chart__svg {
    display: block;
    width: 100%;
    height: auto;
    overflow: visible;
}

.trend-chart__grid line {
    stroke: rgba(var(--v-theme-on-surface), 0.08);
}

.trend-chart__grid text {
    fill: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    font-size: 13px;
}

.trend-chart__line {
    fill: none;
    stroke: rgb(var(--v-theme-primary));
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.trend-chart__point {
    fill: rgb(var(--v-theme-surface));
    stroke: rgb(var(--v-theme-primary));
    stroke-width: 3;
}
</style>
