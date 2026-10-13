<script>
import {
    initAccordions,
    initCarousels,
    initCollapses,
    initDials,
    initDismisses,
    initDrawers,
    initDropdowns,
    initModals,
    initPopovers,
    initTabs,
    initTooltips
} from 'flowbite'

export default {
    mounted() {
        this.initComponents();
    },
    methods: {
        initComponents() {
            initAccordions();
            initCarousels();
            initCollapses();
            initDials();
            initDismisses();
            initDrawers();
            initDropdowns();
            initModals();
            initPopovers();
            initTabs();
            initTooltips();
        }
    },
    render() {
        return this.$slots.default({
            initComponents: this.initComponents,
        });
    },
}
</script>
