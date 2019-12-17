<template>
   <v-container fluid grid-list-md>
       <Loading v-if="loading" />
        <v-layout row v-else>
            <v-flex xs12>
                <!--
                <v-list dark color="transparent" shaped two-line>
                    <v-list-item-group color="deep-purple" v-if="memories.length > 0">
                        <v-list-item v-for="(memory, i) in memories" :key="i" :to="'memory/' + memory.id">
                            <v-list-item-icon>
                                <v-icon v-if="emotions.length > 0" v-text="emotions[memory.emotion_id - 1].icon"></v-icon>
                            </v-list-item-icon>
                            <v-list-item-content>
                                <v-list-item-title v-text="memory.title"></v-list-item-title>
                                <v-list-item-subtitle>{{ formatDate(memory.date) }}</v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                    </v-list-item-group>
                    <div v-else class="mt-3">
                        <p>You haven't added any memories.</p>
                        <p>Get started by adding some below!</p>
                    </div>
                </v-list>
                -->

                <v-row align="center" justify="center">
                    ({{ this.index + 1 }} / {{ this.memories.length }})
                </v-row>

                <v-row justify="center" v-if="current">
                    <Vue2InteractDraggable
                        v-if="isVisible"
                        :interact-out-of-sight-x-coordinate="1000"
                        :interact-max-rotation="15"
                        :interact-x-threshold="200"
                        :interact-y-threshold="200"
                        interact-block-drag-down
                        interact-block-drag-up
                        @draggedLeft="dragLeft()"
                        @draggedRight="dragRight()"
                        class="fixed"
                        style="width: 280px; z-index: 3;">
                        <v-card light class="my-3 card" style="height: 325px;">
                            <v-card-text>
                                <h1 class="title">{{ current.title }}</h1>
                                <div class="mt-2">
                                    {{ formatDate(current.date) }}
                                </div>
                                <div class="mt-2" v-if="current.details">
                                    {{ current.details }}
                                </div>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn outlined block color="teal accent-4" class="text--white" :to="'memory/' + current.id">View</v-btn>
                                <v-spacer></v-spacer>
                            </v-card-actions>
                        </v-card>
                    </Vue2InteractDraggable>
                </v-row>

                <v-row justify="center" v-if="index + 1 < memories.length">
                    <v-card light class="my-3 card card-two fixed" style="height: 325px; width: 280px; z-index: 2;">
                        <v-card-text>
                            <h1 class="title">{{ next.title }}</h1>
                            <div class="mt-2">
                                {{ formatDate(next.date) }}
                            </div>
                            <div class="mt-2" v-if="next.details">
                                {{ next.details }}
                            </div>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn outlined block color="teal accent-4" class="text--white" :to="'memory/' + next.id">View</v-btn>
                            <v-spacer></v-spacer>
                        </v-card-actions>
                    </v-card>
                </v-row>

                <v-row justify="center" v-if="index + 2 < memories.length">
                    <v-card light class="my-3 card card-three fixed" style="height: 325px; width: 280px; z-index: 1;"></v-card>
                </v-row>


                <v-dialog v-model="dialog" fullscreen hide-overlay transition="dialog-bottom-transition">
                    <template v-slot:activator="{ on }">
                        <v-btn class="fixed" color="deep-purple" dark large absolute bottom right fab @click="dialog = true" style="margin-bottom: 50px;">
                            <v-icon>mdi-plus</v-icon>
                        </v-btn>
                    </template>
                    <v-card light>
                        <v-form @submit.prevent="saveMemory" ref="form" autocomplete="off" lazy-validation>
                            <v-toolbar color="transparent" flat>
                                <v-spacer></v-spacer>
                                <v-btn icon @click="dialog = false">
                                    <v-icon>mdi-close</v-icon>
                                </v-btn>
                            </v-toolbar>
                            <div class="text-center">
                                <div v-if="panel == 1">
                                    <div class="body-2 mb-2">
                                        Add a new page to your story!
                                    </div>

                                    <v-date-picker color="deep-purple" v-model="date"></v-date-picker>

                                    <v-footer absolute color="transparent" class="text-center mb-5">
                                        <v-spacer></v-spacer>
                                        <v-btn outlined rounded large color="teal accent-4" dark @click="panel = 2">Next <v-icon>mdi-arrow-right-thick</v-icon></v-btn>
                                        <v-spacer></v-spacer>
                                    </v-footer>
                                </div>
                                <div v-if="panel == 2">
                                    <div class="body-2 mb-2">
                                        How was your day?
                                    </div>

                                    <v-icon color="deep-purple" size="225" v-show="emotion == e.id" v-for="(e, index) in emotions" :key="index">{{ e.icon }}</v-icon>

                                    <v-container grid-list-md>
                                        <v-layout row>
                                            <v-flex xs10 offset-xs1>
                                                <v-slider v-model="emotion" min="1" max="11" color="deep-purple" track-color="teal accent-4"></v-slider>
                                            </v-flex>
                                        </v-layout>
                                        <v-layout row>
                                            <v-flex xs10 offset-xs1 class="text-center">
                                                {{ why }}
                                            </v-flex>
                                        </v-layout>
                                    </v-container>

                                    <v-footer absolute color="transparent" class="text-center mb-5">
                                        <v-spacer></v-spacer>
                                        <v-btn class="ma-1" outlined rounded large color="deep-purple" dark @click="panel = 1"><v-icon>mdi-arrow-left-thick</v-icon> Back</v-btn>
                                        <v-btn class="ma-1" outlined rounded large color="teal accent-4" dark @click="panel = 3">Next <v-icon>mdi-arrow-right-thick</v-icon></v-btn>
                                        <v-spacer></v-spacer>
                                    </v-footer>
                                </div>
                                <div v-if="panel == 3">
                                    <div class="body-2 mb-2">
                                        Why was your day {{ why.toLowerCase() }}?
                                    </div>

                                    <v-list class="text-left">
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

                                    <v-footer absolute color="transparent" class="text-center mb-5">
                                        <v-spacer></v-spacer>
                                        <v-btn class="ma-1" outlined rounded large color="deep-purple" dark @click="panel = 2"><v-icon>mdi-arrow-left-thick</v-icon> Back</v-btn>
                                        <v-btn class="ma-1" outlined rounded large color="teal accent-4" dark @click="panel = 4">Next <v-icon>mdi-arrow-right-thick</v-icon></v-btn>
                                        <v-spacer></v-spacer>
                                    </v-footer>
                                </div>
                                <div v-if="panel == 4">
                                    <div class="body-2 mb-2">
                                        Would you like to add any details?
                                    </div>

                                    <v-container grid-list-md>
                                    <v-layout row wrap>
                                        <v-flex xs12>
                                            <v-text-field outlined name="title" label="Title" :rules="[v => !!v || 'Title is required']" color="teal accent-4" v-model="title"></v-text-field>
                                            <v-textarea
                                            v-model="details"
                                            outlined
                                            name="details"
                                            label="Details (optional)"
                                            color="teal accent-4"
                                            rows="12"
                                            ></v-textarea>
                                        </v-flex>
                                    </v-layout>
                                    </v-container>

                                    <v-footer absolute color="transparent" class="text-center mb-5">
                                        <v-spacer></v-spacer>
                                        <v-btn class="ma-1" outlined rounded large color="deep-purple" dark @click="panel = 3"><v-icon>mdi-arrow-left-thick</v-icon> Back</v-btn>
                                        <v-btn type="submit" class="ma-1" outlined rounded large color="teal accent-4" dark>Done <v-icon>mdi-check-circle</v-icon></v-btn>
                                        <v-spacer></v-spacer>
                                    </v-footer>
                                </div>
                            </div>
                        </v-form>
                    </v-card>
                </v-dialog>
            </v-flex>
        </v-layout>
   </v-container>
</template>

<script>
    import Loading from './../components/Loading'
    import { Vue2InteractDraggable } from 'vue2-interact'

    export default {
        name: 'Home',
        components: {
            Loading,
            Vue2InteractDraggable
        },
        data() {
            return {
                loading: true,
                dialog: false,
                isVisible: true,
                panel: 1,
                title: '',
                index: 0,
                date: new Date().toISOString().substr(0, 10),
                emotion: 6,
                details: '',
                emotions: [],
                reasons: [],
                selectedReasons: [],
                memories: []
            }
        },
        methods: {
            dragLeft() {
                setTimeout(() => this.isVisible = false, 200)
                setTimeout(() => {
                    this.index--
                    this.isVisible = true
                }, 200)
            },
            dragRight() {
                setTimeout(() => this.isVisible = false, 200)
                setTimeout(() => {
                    this.index++
                    this.isVisible = true
                }, 200)
            },
            formatDate(date) {
                return moment(date).format("dddd, MMM Do YYYY")
            },
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
            getMemories() {
                this.loading = true

                axios.get('/api/memories')
                .then(response => {
                    this.memories = response.data
                    this.loading = false
                })
            },
            saveMemory() {
                if (this.$refs.form.validate()) {
                    let date = this.date
                    let emotion = this.emotion
                    let reasons = this.selectedReasons
                    let title = this.title
                    let details = this.details

                    axios.post('/api/memories', { date, emotion, reasons, title, details })
                    .then(response => {
                        this.reset()
                        this.dialog = false
                        // this.getMemories()

                        let memory_id = response.data.data.id
                        if (typeof memory_id != 'undefined' && memory_id != '') {
                            this.$router.push('/memory/' + memory_id)
                        }
                    })
                }
            },
            reset() {
                this.panel = 1
                this.date = new Date().toISOString().substr(0, 10)
                this.emotion = 6
                this.details = ''
                this.title = ''
                this.selectedReasons = []
            },
        },
        computed: {
            current() {
                if ((this.index > (this.memories.length - 1))) {
                    this.index = 0
                }
                if (this.index < 0) {
                    this.index = this.memories.length - 1
                }
                return this.memories[this.index]
            },
            next() {
                return this.memories[this.index + 1]
            },
            why() {
                return this.emotions[this.emotion - 1].emotion
            }
        },
        created() {
            this.getEmotions()
            this.getReasons()
            this.getMemories()
        }
    }
</script>

<style lang="scss" scoped>
    .card-two {
        transform: translate(5%, 5%);
    }
    .card-three {
        transform: translate(10%, 10%);
    }
</style>
