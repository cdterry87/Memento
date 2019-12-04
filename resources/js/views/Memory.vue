<template>
    <v-container fluid grid-list-md>
        <Loading v-if="loading" />
        <v-layout row v-else>
            <v-flex xs10 offset-xs1>
                <div class="headline">
                    {{ memory.title }}
                </div>
                <div class="subheading mt-2">
                    {{ memoryDate }}
                </div>
                <div class="mt-2">
                    <v-tooltip top color="deep-purple">
                        <template v-slot:activator="{ on }">
                            <v-btn text v-on="on"><v-icon>{{ memory.emotion.icon }}</v-icon></v-btn>
                        </template>
                        <span>{{ memory.emotion.emotion }}</span>
                    </v-tooltip>
                    <v-tooltip top color="deep-purple" v-for="(reason, index) in memory.reasons" :key="index">
                        <template v-slot:activator="{ on }">
                            <v-btn text v-on="on"><v-icon>{{ reason.icon }}</v-icon></v-btn>
                        </template>
                        <span>{{ reason.reason }}</span>
                    </v-tooltip>
                </div>
                <div class="mt-4">
                    {{ memory.details }}
                </div>
                <div class="mt-5">
                    <v-divider></v-divider>
                </div>
                <div class="mt-2">
                    <v-system-bar flat color="transparent" height="48" style="padding: 0;">
                        <span>Photos</span>
                        <v-spacer></v-spacer>
                        <v-dialog v-model="dialog" width="500">
                            <template v-slot:activator="{ on }">
                                <v-btn icon small v-on="on">
                                    <v-icon>mdi-plus</v-icon>
                                </v-btn>
                            </template>

                            <v-card light>
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

                                    <v-divider></v-divider>

                                    <v-card-actions>
                                        <v-spacer></v-spacer>
                                        <v-btn type="submit" color="teal accent-4" text>
                                            Upload Photos
                                        </v-btn>
                                        <v-spacer></v-spacer>
                                    </v-card-actions>
                                </v-form>
                            </v-card>
                        </v-dialog>
                    </v-system-bar>
                    <v-container fluid class="pt-0 px-0">
                        <v-row>
                            <v-col v-for="photo in memory.photos" :key="photo.id" cols="4 px-2">
                                <v-avatar size="80" class="pointer elevation-4">
                                    <v-img @click="selectPhoto(photo.id, photo.filename)" :src="photo.filename" aspect-ratio="1" class="deep-purple" :title="photo.filename">
                                        <template v-slot:placeholder>
                                            <v-row class="fill-height ma-0" align="center" justify="center" >
                                                <v-progress-circular indeterminate color="teal accent-4"></v-progress-circular>
                                            </v-row>
                                        </template>
                                    </v-img>
                                </v-avatar>
                            </v-col>
                        </v-row>
                    </v-container>

                    <v-dialog v-model="photo">
                        <v-card light>
                            <v-img :src="selectedPhoto.filename" width="100%">
                                <v-btn @click="photo = false" small class="float-right ma-2">
                                    <v-icon>mdi-close</v-icon>
                                </v-btn>
                            </v-img>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn @click="deletePhoto(selectedPhoto.id)" dark color="red accent-4">
                                    <v-icon>mdi-trash-can</v-icon>
                                    Delete
                                </v-btn>
                                <v-spacer></v-spacer>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </div>
            </v-flex>
        </v-layout>

        <v-speed-dial class="mb-3 fixed" v-model="fab" :bottom="fabBottom" :right="fabRight" :direction="fabDirection" :transition="fabTransition">
            <template v-slot:activator>
                <v-btn v-model="fab" color="deep-purple" dark fab >
                    <v-icon v-if="fab">mdi-close</v-icon>
                    <v-icon v-else>mdi-dots-horizontal</v-icon>
                </v-btn>
            </template>
            <v-btn fab dark small color="red accent-4" @click="deleteMemory"><v-icon>mdi-trash-can</v-icon></v-btn>
            <v-btn fab dark small color="deep-purple"><v-icon>mdi-camera</v-icon></v-btn>
            <v-btn fab dark small color="deep-purple"><v-icon>mdi-square-edit-outline</v-icon></v-btn>
        </v-speed-dial>
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
                dialog: false,
                photo: false,
                fab: false,
                fabBottom: true,
                fabRight: true,
                fabDirection: 'left',
                fabTransition: 'slide-y-reverse-transition',
                loading: true,
                emotions: [],
                reasons: [],
                photos: [],
                selectedPhoto: '',
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
            deleteMemory() {
                axios.delete('/api/memories/' + this.id)
                .then(response => {
                    this.$router.push('/home')
                })
            },
            uploadPhotos() {
                this.dialog = false

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
            selectPhoto(id, filename) {
                this.selectedPhoto = {
                    id: id,
                    filename: filename
                }

                this.photo = true
            },
            deletePhoto(id) {
                this.photo = false
            }
        },
        computed: {
            memoryDate() {
                return moment(this.memory.date).format("dddd, MMM Do YYYY")
            }
        },
        created () {
            this.getMemory()
        },
    }
</script>
