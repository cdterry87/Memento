<template>
    <v-container fluid grid-list-md>
        <Loading v-if="loading" />
        <v-content row v-else>
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
            </v-content>

            <v-divider class="my-5"></v-divider>

            <v-content>
                <v-row v-if="memory.photos.length > 0">
                    <v-col v-for="photo in memory.photos" :key="photo.id" cols="4 px-2">
                        <v-avatar size="80" class="pointer elevation-4">
                            <v-img  :src="photo.filename" aspect-ratio="1" class="deep-purple" :title="photo.filename">
                                <template v-slot:placeholder>
                                    <v-row class="fill-height ma-0" align="center" justify="center" >
                                        <v-progress-circular indeterminate color="teal accent-4"></v-progress-circular>
                                    </v-row>
                                </template>
                            </v-img>
                        </v-avatar>
                    </v-col>
                </v-row>
                <v-row v-else class="mt-4 body-2">
                    You have not added any photos yet.  Click the "+" icon above to add photos of this memory!
                </v-row>
            </v-content>
        </v-content>
    </v-container>
</template>

<script>
    import Loading from './../components/Loading'

    export default {
        name: 'Memory',
        components: {
            Loading
        },
        props: ['id'],
        data() {
            return {
                loading: true,
                emotions: [],
                reasons: [],
                photos: [],
                memory: ''
            }
        },
        methods: {
            getMemory() {
                axios.get('/api/memories/' + this.id)
                .then(response => {
                    this.memory = response.data

                    this.loading = false
                })
            },
            uploadPhotos() {
                let uploadForm = new FormData()
                uploadForm.append('memory_id', this.id)
                for( var i = 0; i < this.photos.length; i++ ){
                    let photo = this.photos[i];

                    uploadForm.append('photos[' + i + ']', photo);
                }

                axios.post('/api/memories/' + this.id + '/upload', uploadForm, { headers: { 'content-type': 'multipart/form-data' } })
                .then(response => {
                    this.getMemory()
                })
            },
        },
        created () {
            this.getMemory()
        },
    }
</script>
