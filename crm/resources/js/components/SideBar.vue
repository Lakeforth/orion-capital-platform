<script>
export default {
    data() {
        return {
            count: 1,
            notifying : false,
            show_side_bar : false,
        };
    },

    methods: {
        increase() {
            this.count++;
        },
        toggleSideBar() {
            this.show_side_bar = !this.show_side_bar;
        }
    },

    render() {
        return this.$slots.default({
            count: this.count,
            notifying : this.notifying,
            increase: this.increase,
            toggleSideBar : this.toggleSideBar,
            show_side_bar : this.show_side_bar,
        });
    },
};
</script>

