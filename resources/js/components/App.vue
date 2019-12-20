<template>
    <div>
        <v-app class="inspire">
            <v-toolbar color="transparent" flat>
                <router-link to="/home">
                    <v-toolbar-title  class="display-1 logo">{{ title }}</v-toolbar-title>
                </router-link>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn text to="/account">
                        <v-icon>mdi-account-circle</v-icon>
                        <span class="ml-3" v-if="$vuetify.breakpoint.mdAndUp">My Account</span>
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-content>
                <router-view></router-view>

                <v-snackbar v-model="snackbar.enabled" :color="snackbar.color" :bottom="true" :timeout="snackbar.timeout">
                    {{ snackbar.message }}
                    <v-btn color="white" text @click="snackbar.enabled = false"><v-icon>mdi-close</v-icon></v-btn>
                </v-snackbar>
            </v-content>

            <div class="footer caption text-center mt-5 mb-3">
                {{ title }} &copy; 2019
            </div>
        </v-app>
    </div>
</template>

<script>
import Event from './../events'

export default {
    name: 'App',
    props: ['title'],
    data() {
        return {
            snackbar: {
                enabled: false,
                message: '',
                timeout: 5000,
                color: ''
            },
        }
    },
    created() {
        Event.$on('success', message => {
            this.snackbar.message = message
            this.snackbar.color = 'success'
            this.snackbar.enabled = true
        })
        Event.$on('warning', message => {
            this.snackbar.message = message
            this.snackbar.color = 'warning'
            this.snackbar.enabled = true
        })
        Event.$on('error', message => {
            this.snackbar.message = message
            this.snackbar.color = 'error'
            this.snackbar.enabled = true
        })
    }
}
</script>

<style lang="scss" scoped>
    .inspire {
        user-select: none;
    }
</style>
