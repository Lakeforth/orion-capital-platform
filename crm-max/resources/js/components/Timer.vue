<template>
    <vue_countdown :time="timeProp" :interval="100" v-slot="{ days, hours, minutes, seconds, milliseconds }">
     {{days}} : {{ hours }} : {{ minutes }} : {{ seconds }}
    </vue_countdown>
</template>

<script>
export default {
    props: {
        timeProp: {
            type: Number,
            required: true,
        },
    },
    data() {
        const now = new Date();

        return {
            time: this.timeProp - now,
        };
    },

};
</script>
