<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
}; ?>

<div class="py-12" x-data="epubReader()">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 border-b pb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Biblioteca (Lector EPUB)</h2>
                    
                    <div class="mt-4 sm:mt-0 flex items-center">
                        <label class="cursor-pointer bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded shadow transition text-sm font-semibold inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Cargar Libro (.epub)
                            <input type="file" class="hidden" accept=".epub" @change="loadBook($event)">
                        </label>
                    </div>
                </div>

                <!-- Lector -->
                <div x-show="bookLoaded" style="display: none;" x-cloak>
                    <div class="flex justify-between items-center mb-4">
                        <button @click="prevPage" class="p-2 bg-gray-200 hover:bg-gray-300 rounded-full text-gray-700 transition focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        
                        <div class="text-sm text-gray-600 font-bold truncate px-4" x-text="bookTitle"></div>

                        <button @click="nextPage" class="p-2 bg-gray-200 hover:bg-gray-300 rounded-full text-gray-700 transition focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>

                    <div id="viewer" class="w-full h-[65vh] border border-gray-200 rounded-lg bg-[#fdfdfd] shadow-inner overflow-hidden relative"></div>
                </div>

                <!-- Estado Inicial -->
                <div x-show="!bookLoaded" class="py-20 text-center">
                    <svg class="w-20 h-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <p class="text-gray-600 font-medium mb-2">No hay ningún libro cargado.</p>
                    <p class="text-sm text-gray-400">Selecciona un archivo .epub desde tu dispositivo para comenzar a leer.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/epubjs/dist/epub.min.js"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('epubReader', () => ({
            bookLoaded: false,
            bookTitle: 'Cargando libro...',
            book: null,
            rendition: null,

            loadBook(event) {
                const file = event.target.files[0];
                if (!file) return;

                if (window.FileReader) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.bookLoaded = true;
                        this.initEpub(e.target.result);
                    };
                    reader.readAsArrayBuffer(file);
                }
            },

            initEpub(bookData) {
                document.getElementById("viewer").innerHTML = "";
                
                this.book = ePub(bookData);
                this.rendition = this.book.renderTo("viewer", {
                    width: "100%",
                    height: "100%",
                    spread: "none",
                    manager: "continuous",
                    flow: "paginated"
                });

                this.rendition.display();

                this.book.ready.then(() => {
                    const meta = this.book.package.metadata;
                    this.bookTitle = meta.title || 'Libro sin título';
                });

                this.rendition.on("keyup", (e) => {
                    if ((e.keyCode || e.which) == 37) {
                        this.book.package.metadata.direction === "rtl" ? this.nextPage() : this.prevPage();
                    }
                    if ((e.keyCode || e.which) == 39) {
                        this.book.package.metadata.direction === "rtl" ? this.prevPage() : this.nextPage();
                    }
                });

                window.addEventListener("resize", () => {
                    if(this.rendition) {
                        this.rendition.resize("100%", "100%");
                    }
                });
            },

            nextPage() {
                if (this.rendition) {
                    this.rendition.next();
                }
            },

            prevPage() {
                if (this.rendition) {
                    this.rendition.prev();
                }
            }
        }));
    });
</script>
