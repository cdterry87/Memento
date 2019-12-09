<template>
    <div>
        <v-app class="inspire">
            <v-toolbar color="transparent" flat>
                <v-toolbar-title>
                    <a href="/home" class="display-1 logo">{{ title }}</a>
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn text to="/home" v-if="this.$router.currentRoute.name != 'home'">
                        <v-icon>mdi-home</v-icon>
                        <span class="ml-3" v-if="$vuetify.breakpoint.mdAndUp">Home</span>
                    </v-btn>
                    <v-btn text to="/account">
                        <v-icon>mdi-account-circle</v-icon>
                        <span class="ml-3" v-if="$vuetify.breakpoint.mdAndUp">My Account</span>
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-content>

                <v-form method="POST" enctype="multipart/form-data" id="uploadForm" @submit.prevent="uploadPhotos" ref="uploadForm" autocomplete="off" lazy-validation>
                    <v-card-text>
                        <v-file-input v-model="photos" color="teal accent-4" outlined show-size counter multiple label="Photos" accept="image/*" placeholder="Upload photos of your day" prepend-inner-icon="mdi-camera" prepend-icon="">
                            <template v-slot:selection="{ index, text }">
                                <v-chip v-if="index < 2" color="deep-purple accent-4" dark label small>
                                    {{ text }}
                                </v-chip>
                                <span v-else-if="index === 2" class="overline grey--text text--darken-3 mx-2">
                                    +{{ photos.length - 2 }} Photo(s)
                                </span>
                            </template>
                        </v-file-input>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn type="submit" color="white" outlined>
                            Upload Photos
                        </v-btn>
                        <v-spacer></v-spacer>
                    </v-card-actions>
                </v-form>

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
            photos: [],
            snackbar: {
                enabled: false,
                message: '',
                timeout: 5000,
                color: ''
            },
        }
    },
    methods: {
        uploadPhotos() {
            let uploadForm = new FormData()
            uploadForm.append('memory_id', this.id)
            for( var i = 0; i < this.photos.length; i++ ){
                let photo = this.photos[i];

                uploadForm.append('photos[' + i + ']', photo);
            }
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
