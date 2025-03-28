<template>
    <v-container fluid grid-list-md>
        <Loading v-if="loading" />
        <v-layout row v-else>
            <v-flex xs10 offset-xs1 v-if="editMode" class="animated" :class="{ fadeInUp: editMode }">
                <v-form method="POST" id="saveForm" @submit.prevent="saveMemory" ref="saveForm" autocomplete="off" lazy-validation>
                    <div>
                        <v-text-field outlined hide-details v-model="memory.title" label="Title" color="white" prepend-inner-icon="mdi-book-open-page-variant" :rules="[v => !!v || 'Title is required']" required></v-text-field>
                    </div>
                    <div class="my-3">
                        <v-dialog ref="datePicker" v-model="datePickerDialog" :return-value.sync="date" width="300px">
                            <template v-slot:activator="{ on }">
                                <v-text-field v-model="memory.date" label="Date" hide-details prepend-inner-icon="mdi-calendar" readonly color="white" v-on="on" outlined :rules="[v => !!v || 'Date is required']" required></v-text-field>
                            </template>
                            <v-date-picker light color="deep-purple" v-model="memory.date" scrollable>
                                <v-spacer></v-spacer>
                                <v-btn text @click="datePickerDialog = false">Cancel</v-btn>
                                <v-btn text color="teal accent-4" @click="$refs.datePicker.save(memory.date)">OK</v-btn>
                            </v-date-picker>
                        </v-dialog>
                    </div>
                    <div class="my-4">
                        <v-textarea outlined hide-details label="Details" v-model="memory.details" color="white" auto-grow></v-textarea>
                    </div>
                    <div class="my-3">
                        <div class="text-center my-3">
                            <v-spacer></v-spacer>
                            <v-btn color="white" outlined dark class="mx-2" @click="editMode = false">Cancel</v-btn>
                            <v-btn type="submit" color="deep-purple" outlined dark class="mx-2">Save</v-btn>
                            <v-spacer></v-spacer>
                        </div>
                    </div>
                </v-form>
            </v-flex>
            <v-flex xs10 offset-xs1 v-else class="animated" :class="{ fadeOutDown: editMode, fadeIn: !editMode }">
                <div>
                    <span class="headline">{{ memory.title }}</span>
                </div>
                <div class="my-3">
                    <span class="mt-2 subheading">{{ memoryDate }}</span>
                </div>
                <div class="my-4">
                    <span class="mt-4">{{ memory.details }}</span>
                </div>
                <div class="mt-2">
                    <v-tooltip top color="deep-purple" v-if="memory.emotion">
                        <template v-slot:activator="{ on }">
                            <v-btn icon text v-on="on"><v-icon>{{ memory.emotion.icon }}</v-icon></v-btn>
                        </template>
                        <span>{{ memory.emotion.emotion }}</span>
                    </v-tooltip>
                    <v-tooltip top color="deep-purple" v-for="(reason, index) in memory.reasons" :key="index">
                        <template v-slot:activator="{ on }">
                            <v-btn icon text v-on="on" class="mx-2"><v-icon>{{ reason.icon }}</v-icon></v-btn>
                        </template>
                        <span>{{ reason.reason }}</span>
                    </v-tooltip>
                </div>
                <div class="mt-5">
                    <v-divider></v-divider>
                </div>
                <div class="mt-2">
                    <v-system-bar flat color="transparent" height="48" class="p-0">
                        <span class="title">Photos</span>
                        <v-spacer></v-spacer>
                        <v-dialog v-model="uploadPhotosDialog" width="500">
                            <template v-slot:activator="{ on }">
                                <v-btn icon small v-on="on">
                                    <v-icon>mdi-plus</v-icon>
                                </v-btn>
                            </template>

                            <v-card light>
                                <v-form method="POST" enctype="multipart/form-data" id="uploadForm" @submit.prevent="uploadPhotos" ref="uploadForm" autocomplete="off" lazy-validation>
                                    <v-card-text>
                                        <v-file-input v-model="uploads" color="teal accent-4" outlined show-size counter multiple label="Photos" accept="image/*" placeholder="Upload photos of your day" prepend-inner-icon="mdi-camera" prepend-icon="">
                                            <template v-slot:selection="{ index, text }">
                                                <v-chip v-if="index < 2" color="deep-purple accent-4" dark label small>
                                                    {{ text }}
                                                </v-chip>
                                                <span v-else-if="index === 2" class="overline grey--text text--darken-3 mx-2">
                                                    +{{ uploads.length - 2 }} Photo(s)
                                                </span>
                                            </template>
                                        </v-file-input>
                                    </v-card-text>
                                    <v-card-actions>
                                        <v-spacer></v-spacer>
                                        <v-btn small text @click="uploadPhotosDialog = false">Cancel</v-btn>
                                        <v-btn type="submit" color="teal accent-4" small text>Upload Photos</v-btn>
                                        <v-spacer></v-spacer>
                                    </v-card-actions>
                                </v-form>
                            </v-card>
                        </v-dialog>
                    </v-system-bar>
                    <v-container fluid class="pt-0 px-0" v-if="memory.photos">
                        <v-row v-if="memory.photos.length > 0">
                            <v-col v-for="photo in memory.photos" :key="photo.id" cols="4 px-2" align="center">
                                <v-avatar :size="($vuetify.breakpoint.lgAndUp ? 160 : 80)" class="pointer elevation-4">
                                    <v-img @click="selectPhoto(photo.id, photo.filename)" :src="photo.filename" aspect-ratio="1" class="deep-purple">
                                        <template v-slot:placeholder>
                                            <v-row class="fill-height ma-0" align="center" justify="center" >
                                                <v-progress-circular indeterminate color="teal accent-4"></v-progress-circular>
                                            </v-row>
                                        </template>
                                    </v-img>
                                </v-avatar>
                            </v-col>
                        </v-row>
                        <v-row v-else class="mt-4 ml-2 body-2">
                            You have not added any photos yet.  Click the "+" icon above to add photos of this memory!
                        </v-row>
                    </v-container>

                    <v-dialog v-model="viewPhotosDialog" hide-overlay transition="dialog-bottom-transition">
                        <v-card light>
                            <v-container>
                                <v-system-bar flat color="transparent" height="48" class="py-3">
                                    <v-btn @click="deletePhoto(selectedPhoto.id)" icon>
                                        <v-icon color="red accent-4">mdi-trash-can</v-icon>
                                    </v-btn>
                                    <v-spacer></v-spacer>
                                    <span class="caption" v-if="selectedPhoto.filename == memory.photo">Primary Image</span>
                                    <v-spacer></v-spacer>
                                    <v-btn @click="viewPhotosDialog = false" icon>
                                        <v-icon color="deep-purple">mdi-close</v-icon>
                                    </v-btn>
                                </v-system-bar>
                                <v-content align="center" justify="center" class="px-5 mt-5">
                                    <img :src="selectedPhoto.filename" width="100%" class="radius elevation-4" alt="">
                                </v-content>
                                <v-system-bar flat color="transparent" height="48" class="py-3 mt-5">
                                    <v-btn icon @click="prevPhoto()">
                                        <v-icon color="teal accent-4">mdi-arrow-left-thick</v-icon>
                                    </v-btn>
                                    <v-spacer></v-spacer>
                                    <v-btn small text v-if="selectedPhoto.filename != memory.photo" @click="selectPrimaryPhoto(selectedPhoto.filename)">Make Primary</v-btn>
                                    <v-btn small text v-else @click="selectPrimaryPhoto()">Remove Primary</v-btn>
                                    <v-spacer></v-spacer>
                                    <v-btn icon @click="nextPhoto()">
                                        <v-icon color="teal accent-4">mdi-arrow-right-thick</v-icon>
                                    </v-btn>
                                </v-system-bar>
                            </v-container>
                        </v-card>
                    </v-dialog>
                </div>
            </v-flex>
        </v-layout>

        <v-dialog v-model="deleteMemoryPrompt" hide-overlay width="300px">
            <v-card light>
                <v-card-text class="py-4 title text-center">
                    <div >
                        Are you sure you want to delete this memory?
                    </div>
                </v-card-text>
                <v-card-actions class="py-4">
                    <v-spacer></v-spacer>
                    <v-btn small text @click="deleteMemoryPrompt = false">Cancel</v-btn>
                    <v-btn small text color="red" @click="deleteMemory">Delete Memory</v-btn>
                    <v-spacer></v-spacer>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="editEmojiDialog" hide-overlay width="300px">
            <v-card light>
                <v-card-text class="title text-center">
                    <v-icon color="deep-purple" size="150" v-show="memory.emotion_id == e.id" v-for="(e, index) in emotions" :key="index">{{ e.icon }}</v-icon>
                    <v-container grid-list-md>
                        <v-layout row>
                            <v-flex xs10 offset-xs1>
                                <v-slider v-model="memory.emotion_id" hide-details min="1" max="11" color="deep-purple" track-color="teal accent-4"></v-slider>
                            </v-flex>
                        </v-layout>
                    </v-container>
                    <v-divider class="my-3"></v-divider>
                    <v-list class="text-left" v-if="memory.reasons">
                        <v-list-item v-for="(reason, index) in reasons" :key="index">
                            <v-list-item-action>
                                <v-icon color="deep-purple">{{ reason.icon }}</v-icon>
                            </v-list-item-action>
                            <v-list-item-content>
                                <v-list-item-title>{{ reason.reason }}</v-list-item-title>
                            </v-list-item-content>
                            <v-list-item-action>
                                <v-checkbox
                                    color="teal accent-4"
                                    v-model="selectedReasons[index]"
                                ></v-checkbox>
                            </v-list-item-action>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions class="py-4">
                    <v-spacer></v-spacer>
                    <v-btn small text @click="editEmojiDialog = false">Cancel</v-btn>
                    <v-btn small text color="teal accent-4" @click="saveEmojis">Save Emojis</v-btn>
                    <v-spacer></v-spacer>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="primaryPhotoDialog" width="300px">
            <v-card light>
                <v-card-text class="pt-4">
                    <v-subheader class="subheading">Select a primary photo for this memory</v-subheader>
                    <v-container fluid grid-list-md fill-height>
                        <v-layout row wrap align-center>
                            <v-flex xs6 class="text-center" @click="selectPrimaryPhoto()">
                                <v-img aspect-ratio="1" class="pointer transparent elevation-4">
                                    <v-row class="fill-height ma-0" align="center" justify="center">
                                        <v-icon size="48" title="Remove primary photo">mdi-cancel</v-icon>
                                    </v-row>
                                    <v-row align="end" class="pa-0 fill-height float-right" v-if="memory.photo == ''">
                                         <v-col>
                                            <v-icon size="32" color="teal accent-4">mdi-check-circle-outline</v-icon>
                                         </v-col>
                                    </v-row>
                                </v-img>
                            </v-flex>
                            <v-flex xs6 v-for="photo in memory.photos" :key="photo.id" @click="selectPrimaryPhoto(photo.filename)">
                                <v-img :src="photo.filename" aspect-ratio="1" class="pointer deep-purple elevation-4">
                                    <template v-slot:placeholder>
                                        <v-row class="fill-height ma-0" align="center" justify="center" >
                                            <v-progress-circular indeterminate color="teal accent-4"></v-progress-circular>
                                        </v-row>
                                    </template>
                                    <v-row align="end" class="pa-0 fill-height float-right" v-if="photo.filename == memory.photo">
                                         <v-col>
                                            <v-icon size="32" color="teal accent-4">mdi-check-circle-outline</v-icon>
                                         </v-col>
                                    </v-row>
                                </v-img>
                            </v-flex>
                        </v-layout>
                    </v-container>
                </v-card-text>
                <v-card-actions class="pb-4">
                    <v-spacer></v-spacer>
                    <v-btn small text @click="primaryPhotoDialog = false">Cancel</v-btn>
                    <v-spacer></v-spacer>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-speed-dial class="mb-3 fixed" v-model="fab" :bottom="fabBottom" :right="fabRight" :direction="fabDirection" :transition="fabTransition">
            <template v-slot:activator>
                <v-btn v-model="fab" color="deep-purple" dark fab >
                    <v-icon v-if="fab">mdi-close</v-icon>
                    <v-icon v-else>mdi-dots-horizontal</v-icon>
                </v-btn>
            </template>
            <v-btn fab dark small color="red accent-4" @click="deleteMemoryPrompt = true"><v-icon>mdi-trash-can</v-icon></v-btn>
            <v-btn fab dark small color="deep-purple" @click="primaryPhotoDialog = true"><v-icon>mdi-camera</v-icon></v-btn>
            <v-btn fab dark small color="deep-purple" @click="editEmojiDialog = true"><v-icon>mdi-emoticon-excited-outline</v-icon></v-btn>
            <v-btn fab dark small color="deep-purple" @click="editMode = true"><v-icon>mdi-square-edit-outline</v-icon></v-btn>
        </v-speed-dial>
    </v-container>
</template>

<script>
    import Loading from './../components/Loading'
    import Event from './../events'

    export default {
        name: 'Memory',
        components: {
            Loading
        },
        props: ['id'],
        data() {
            return {
                uploadPhotosDialog: false,
                viewPhotosDialog: false,
                deleteMemoryPrompt: false,
                primaryPhotoDialog: false,
                datePickerDialog: false,
                editEmojiDialog: false,
                loading: true,
                editMode: false,
                fab: false,
                fabBottom: true,
                fabRight: true,
                fabDirection: 'left',
                fabTransition: 'slide-y-reverse-transition',
                title: '',
                date: new Date().toISOString().substr(0, 10),
                emotion: 6,
                details: '',
                emotions: [],
                reasons: [],
                selectedReasons: [],
                uploads: [],
                selectedPhoto: '',
                memory: ''
            }
        },
        methods: {
            getEmotions() {
                axios.get('/api/emotions')
                .then(response => {
                    this.emotions = response.data
                })
            },
            getReasons() {
                axios.get('/api/reasons')
                .then(response => {
                    this.reasons = response.data
                })
            },
            getMemory() {
                let vm = this

                axios.get('/api/memories/' + this.id)
                .then(response => {
                    this.memory = response.data
                    this.photos = response.data.photos

                    this.getSelectedReasons()

                    this.loading = false
                })
                .catch(function (error) {
                    vm.loading = false
                    vm.$router.push('/home')
                    Event.$emit('error', 'An error occurred while loading this memory. Try again later.')
                });
            },
            // Define the reasons that are already selected for this memory
            getSelectedReasons() {
                this.selectedReasons = []

                this.reasons.forEach(reason => {
                    if (this.memory.reasons.find(rsn => rsn.id == reason.id)) {
                        this.selectedReasons.push(true)
                    } else {
                        this.selectedReasons.push(false)
                    }
                })
            },
            saveMemory() {
                if (this.$refs.saveForm.validate()) {
                    let date = this.memory.date
                    let title = this.memory.title
                    let details = this.memory.details
                    let user_id = this.memory.user_id

                    axios.put('/api/memories/' + this.id, { date, title, details, user_id })
                    .then(response => {
                        Event.$emit('success', response.data.message)

                        this.editMode = false
                    })
                }
            },
            saveEmojis() {
                let id = this.id
                let emotion_id = this.memory.emotion_id
                let reasons = this.selectedReasons
                let user_id = this.memory.user_id

                axios.post('/api/memories/' + this.id + '/emojis', { id, emotion_id, reasons, user_id })
                .then(response => {
                    this.getMemory()

                    this.getSelectedReasons()

                    Event.$emit('success', response.data.message)

                    this.editEmojiDialog = false
                })
            },
            deleteMemory() {
                axios.delete('/api/memories/' + this.id)
                .then(response => {
                    this.$router.push('/home')

                    Event.$emit('error', response.data.message)
                })
            },
            uploadPhotos() {
                this.dialog = false

                let uploadForm = new FormData()
                uploadForm.append('memory_id', this.id)
                for( var i = 0; i < this.uploads.length; i++ ){
                    let photo = this.uploads[i];

                    uploadForm.append('photos[' + i + ']', photo);
                }

                axios.post('/api/memories/' + this.id + '/upload', uploadForm, { headers: { 'content-type': 'multipart/form-data' } })
                .then(response => {
                    this.getMemory()

                    Event.$emit('success', response.data.message)

                    this.uploads = []
                })
            },
            selectPhoto(id, filename) {
                this.selectedPhoto = {
                    id: id,
                    filename: filename
                }

                this.viewPhotosDialog = true
            },
            selectPrimaryPhoto(photo) {
                let id = this.id

                if (typeof(photo) == 'undefined') {
                    photo = ''
                }

                axios.post('/api/memories/' + id + '/photo', { id, photo })
                .then(response => {
                    Event.$emit('success', response.data.message)
                    this.getMemory();
                })
            },
            deletePhoto(id) {
                this.viewPhotosDialog = false

                axios.delete('/api/photo/' + id)
                .then(response => {
                    this.getMemory()

                    Event.$emit('error', response.data.message)
                })
            },
            findPhoto(id) {
                return this.photos.find((photo) => {
                    return photo.id === id
                })
            },
            prevPhoto() {
                let photo = ''

                const index = this.photos.findIndex((photo) => photo.id === this.selectedPhoto.id)
                let len = this.photos.length - 1;
                if (index == 0) {
                    photo = this.photos[this.photos.length - 1]
                } else {
                    photo = this.photos[index - 1]
                }

                this.selectPhoto(photo.id, photo.filename)
            },
            nextPhoto() {
                let photo = ''

                const index = this.photos.findIndex((photo) => photo.id === this.selectedPhoto.id)
                if(index >= 0 && index < this.photos.length - 1) {
                    photo = this.photos[index + 1]
                } else {
                    photo = this.photos[0]
                }

                this.selectPhoto(photo.id, photo.filename)
            }
        },
        computed: {
            memoryDate() {
                return moment(this.memory.date).format("dddd, MMM Do YYYY")
            },
            why() {
                if (this.emotions.length > 0) {
                    return this.emotions[this.emotion - 1].emotion
                }
                return false
            }
        },
        created () {
            this.getEmotions()
            this.getReasons()
            this.getMemory()
        },
    }
</script>

<style lang="scss">
    img {
        image-orientation: from-image !important;
    }
</style>
