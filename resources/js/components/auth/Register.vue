<template>
    <v-app id="inspire" dark>
        <v-content>
            <v-container fluid fill-height>
                <v-layout align-center justify-center wrap>
                    <v-flex xs12 sm10 md5>
                        <div class="text-center mb-3">
                            <h1 class="display-1 logo">{{ title }}</h1>
                            <h2 class="title mt-2">Sign up to keep track of your memories!</h2>
                        </div>
                        <div class="mt-3 mb-5" v-if="errorMessages.length > 0">
                            <Errors :errors="errorMessages" />
                        </div>
                        <v-text-field color="white" label="Full Name" filled prepend-inner-icon="mdi-account" id="name" name="name" type="text" required></v-text-field>
                        <v-text-field color="white" label="Email" filled prepend-inner-icon="mdi-email" id="email" name="email" type="text" required></v-text-field>
                        <v-text-field color="white" label="Password" filled prepend-inner-icon="mdi-lock" id="password" name="password" type="password" required></v-text-field>
                        <v-text-field color="white" label="Confirm Password" filled prepend-inner-icon="mdi-lock" id="password_confirmation" name="password_confirmation" type="password" required></v-text-field>
                        <div class="text-center">
                            <v-btn outlined type="submit">Register</v-btn>
                        </div>
                        <div class="text-center mt-5">
                            <v-btn text href="/login">
                                <v-icon>mdi-arrow-left</v-icon>
                                <span>Back to Login</span>
                            </v-btn>
                        </div>
                        <v-container>
                            <v-layout align-center class="mt-2">
                                <v-flex xs12>
                                    <v-divider></v-divider>
                                    <div class="mt-4 text-center">
                                        &copy; {{ title }} {{ new Date().getFullYear()}}
                                    </div>
                                </v-flex>
                            </v-layout>
                        </v-container>
                    </v-flex>
                </v-layout>
            </v-container>
        </v-content>
    </v-app>
</template>

<script>
    import Errors from './../Errors'

    export default {
        name: 'Register',
        props: ['title', 'errors'],
        components: {
            Errors
        },
        computed: {
            errorMessages: function() {
                return JSON.parse(this.errors)
            }
        }
    }
</script>
