<template>
    <button @click="toggleTheme" id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400
    hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none
     rounded-lg text-sm p-2.5">
<!--        <svg v-if="darkIconVisible" id="theme-toggle-dark-icon"></svg>-->
<!--        <svg v-if="lightIconVisible" id="theme-toggle-light-icon"></svg>-->
        <svg v-if="lightIconVisible" class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
            <path d="M17.8 13.75a1 1 0 0 0-.859-.5A7.488 7.488 0 0 1 10.52 2a1 1 0 0 0 0-.969A1.035 1.035 0 0 0 9.687.5h-.113a9.5 9.5 0 1 0 8.222 14.247 1 1 0 0 0 .004-.997Z"/>
        </svg>

        <svg  v-if="darkIconVisible" class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 20">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.509 5.75c0-1.493.394-2.96 1.144-4.25h-.081a8.5 8.5 0 1 0 7.356 12.746A8.5 8.5 0 0 1 8.509 5.75Z"/>
        </svg>
    </button>
</template>

<script>
export default {
    data() {
        return {
            darkIconVisible: false,
            lightIconVisible: false
        };
    },
    mounted() {
        this.checkColorTheme();
    },
    methods: {
        checkColorTheme() {
            const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
            const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

            if (localStorage.getItem('color-theme') === 'dark' || (!localStorage.getItem('color-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                this.lightIconVisible = true;
            } else {
                this.darkIconVisible = true;
            }
        },
        toggleTheme() {
            const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
            const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

            this.darkIconVisible = !this.darkIconVisible;
            this.lightIconVisible = !this.lightIconVisible;

            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            } else {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            }
        }
    }
};
</script>

<style>
/* Add your button and icon styles here */
</style>
