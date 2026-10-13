<script>
export default {
    props: {
        form: Object,
    },

    mounted() {
        this.form.items = [];
    },

    methods: {
        addItem() {
            this.form.items.push({ name: '', price: '' });
        },
    },

    render() {
        return this.$slots.default({});
    },
};
</script>

