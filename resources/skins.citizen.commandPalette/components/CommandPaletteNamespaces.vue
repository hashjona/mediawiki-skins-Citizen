<template>
	<div
		v-if="!activeMode && namespaces.length > 0"
		class="citizen-command-palette-namespaces"
	>
		<button
			v-for="ns in namespaces"
			:key="ns.id"
			class="citizen-command-palette-namespaces__button"
			:class="{
				'citizen-command-palette-namespaces__button--active':
					ns.id === activeNamespaceId,
			}"
			:title="ns.name"
			@click="$emit('select-namespace', ns)"
		>
			{{ ns.label }}
		</button>
	</div>
</template>

<script>
const { defineComponent, computed } = require("vue");
const config = require("../config.json");

const MAIN_NAMESPACE_ID = "0";

// @vue/component
module.exports = exports = defineComponent({
	name: "CommandPaletteNamespaces",
	props: {
		tokens: {
			type: Array,
			default: () => [],
		},
		activeMode: {
			type: Object,
			default: null,
		},
	},
	emits: ["select-namespace"],
	setup(props) {
		const allowedIds = (config.wgCitizenSearchNamespaceButtons || []).map(
			String,
		);

		const namespaces = computed(() => {
			const formattedNamespaces =
				mw.config.get("wgFormattedNamespaces") || {};
			const entries = [];

			// Add Main namespace first if configured
			if (allowedIds.includes(MAIN_NAMESPACE_ID)) {
				entries.push({
					id: MAIN_NAMESPACE_ID,
					name: "",
					label: mw
						.message("citizen-command-palette-namespace-main")
						.text(),
				});
			}

			// Add configured namespaces, preserving config order
			allowedIds
				.filter(
					(id) => id !== MAIN_NAMESPACE_ID && formattedNamespaces[id],
				)
				.forEach((id) => {
					const name = formattedNamespaces[id];
					entries.push({ id, name, label: name });
				});

			return entries;
		});

		const activeNamespaceId = computed(() => {
			const nsToken = props.tokens.find((t) => t.modeId === "namespace");
			if (!nsToken) {
				// No namespace token means searching in main namespace
				return MAIN_NAMESPACE_ID;
			}
			// Token raw is like "Talk:", strip the trailing colon to match
			const raw = nsToken.raw.replace(/:$/, "");
			const match = namespaces.value.find((ns) => ns.name === raw);
			return match ? match.id : null;
		});

		return {
			namespaces,
			activeNamespaceId,
		};
	},
});
</script>

<style lang="less">
@import "mediawiki.skin.variables.less";

.citizen-command-palette-namespaces {
	display: flex;
	gap: @spacing-25;
	padding: 0 var(--citizen-command-palette-side-padding) var(--space-xs);
	overflow-x: auto;
	overscroll-behavior-x: contain;
	-webkit-overflow-scrolling: touch;

	/* Hide scrollbar but keep scrollable */
	scrollbar-width: none;

	&::-webkit-scrollbar {
		display: none;
	}

	&__button {
		flex-shrink: 0;
		padding: @spacing-25 @spacing-75;
		color: var(--color-base);
		font-size: var(--font-size-small);
		line-height: var(--line-height-xx-small);
		white-space: nowrap;
		cursor: pointer;
		background-color: transparent;
		border: 0;
		border-radius: var(--border-radius-pill);
		transition-timing-function: var(--transition-timing-function-ease);
		transition-duration: var(--transition-duration-base);
		transition-property: background-color, color, opacity;

		&:not(.citizen-command-palette-namespaces__button--active):hover {
			opacity: var(--opacity-base);
		}

		&--active {
			color: var(--color-inverted-fixed);
			background-color: var(--color-progressive);
		}
	}
}
</style>
