<?php
use App\Models\Book;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $books;
    
    #[Validate('required|string|max:255')]
    public $title;
    
    #[Validate('nullable|string|max:255')]
    public $author;
    
    #[Validate('required|file|mimes:epub')]
    public $file;

    #[Validate('nullable|image|max:2048')]
    public $cover;
    
    public $showUploadForm = false;

    public function mount()
    {
        $this->loadBooks();
    }

    public function loadBooks()
    {
        $this->books = Book::orderBy('created_at', 'desc')->get();
    }

    public function toggleUploadForm()
    {
        $this->showUploadForm = !$this->showUploadForm;
    }

    public function saveBook()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $this->validate();

        $path = $this->file->store('books', 'public');
        
        $coverPath = null;
        if ($this->cover) {
            $coverPath = $this->cover->store('book-covers', 'public');
        }

        Book::create([
            'title' => $this->title,
            'author' => $this->author,
            'file_path' => $path,
            'cover_path' => $coverPath,
        ]);

        $this->reset(['title', 'author', 'file', 'cover', 'showUploadForm']);
        $this->loadBooks();
        session()->flash('status', 'Libro guardado con éxito.');
    }
    
    public function deleteBook($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }
        $book = Book::find($id);
        if($book) {
            Storage::disk('public')->delete($book->file_path);
            if ($book->cover_path) {
                Storage::disk('public')->delete($book->cover_path);
            }
            $book->delete();
            $this->loadBooks();
        }
    }
}; ?>

<div class="py-12" x-data="epubReader()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Vista de la Biblioteca (Grid) -->
        <div x-show="!readerOpen" x-transition>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Biblioteca Central</h2>
                @if(auth()->user()->role === 'admin')
                    <button wire:click="toggleUploadForm" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded shadow transition text-sm font-semibold inline-flex items-center gap-2">
                        {{ $showUploadForm ? 'Cancelar' : '+ Añadir Libro' }}
                    </button>
                @endif
            </div>

            @if(session('status'))
                <div class="mb-4 bg-green-50 text-green-700 p-4 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            @if(auth()->user()->role === 'admin' && $showUploadForm)
                <div class="bg-white p-6 rounded-lg shadow-sm mb-6 border border-gray-200">
                    <h3 class="text-lg font-bold mb-4">Subir Nuevo Libro (EPUB)</h3>
                    <form wire:submit="saveBook" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="title" value="Título del Libro *" />
                                <x-text-input wire:model="title" id="title" type="text" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="author" value="Autor (Opcional)" />
                                <x-text-input wire:model="author" id="author" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="file" value="Archivo .epub *" />
                                <input type="file" wire:model="file" id="file" accept=".epub" class="mt-1 block w-full border border-gray-300 rounded-md p-2 text-sm" required>
                                <div wire:loading wire:target="file" class="text-sm text-indigo-600 mt-2">Subiendo archivo epub...</div>
                                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="cover" value="Portada del Libro (Opcional) - JPG/PNG" />
                                <input type="file" wire:model="cover" id="cover" accept="image/jpeg, image/png, image/webp" class="mt-1 block w-full border border-gray-300 rounded-md p-2 text-sm">
                                <div wire:loading wire:target="cover" class="text-sm text-indigo-600 mt-2">Subiendo imagen...</div>
                                <x-input-error :messages="$errors->get('cover')" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <x-primary-button wire:loading.attr="disabled" wire:target="saveBook, file, cover">Guardar Libro</x-primary-button>
                        </div>
                    </form>
                </div>
            @endif

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1.5rem;">
                @forelse($books as $book)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition cursor-pointer relative group flex flex-col" 
                         @click="openBook('{{ asset('storage/' . $book->file_path) }}', '{{ addslashes($book->title) }}', {{ $book->id }})">
                        
                        @if($book->cover_path)
                            <div class="w-full bg-gray-100 flex items-center justify-center overflow-hidden" style="aspect-ratio: 2/3;">
                                <img src="{{ asset('storage/' . $book->cover_path) }}" alt="{{ $book->title }}" class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="bg-gradient-to-br from-indigo-100 to-purple-100 flex flex-col items-center justify-center p-4 text-center" style="aspect-ratio: 2/3;">
                                <svg class="w-12 h-12 text-indigo-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </div>
                        @endif

                        <div class="p-3 flex-1 flex flex-col justify-center">
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-2 leading-tight">{{ $book->title }}</h3>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $book->author }}</p>
                        </div>
                        
                        @if(auth()->user()->role === 'admin')
                            <button wire:click.stop="deleteBook({{ $book->id }})" wire:confirm="¿Borrar este libro definitivamente? Esto eliminará el archivo epub y la portada del servidor." class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 shadow hover:bg-red-600 focus:outline-none transition z-10">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center bg-white rounded-lg shadow-sm border border-gray-100" style="grid-column: 1 / -1;">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <p class="text-gray-500 font-medium mb-2">La biblioteca está vacía.</p>
                        @if(auth()->user()->role === 'admin')
                            <p class="text-sm text-gray-400">Haz clic en "+ Añadir Libro" arriba para subir tu primer EPUB a la plataforma.</p>
                        @else
                            <p class="text-sm text-gray-400">Pronto un administrador subirá libros interesantes para tu crecimiento.</p>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Vista del Lector -->
        <div x-show="readerOpen" style="display: none;" 
             :class="isFullscreen ? 'fixed inset-0 z-[100] bg-white flex flex-col' : 'bg-white rounded-lg shadow-sm overflow-hidden flex flex-col'" 
             x-transition x-cloak>
            
            <!-- Header del Lector -->
            <div class="p-4 border-b flex justify-between items-center transition-colors duration-300 bg-gray-50 border-gray-200 text-gray-800">
                <button @click="closeBook" class="inline-flex items-center gap-2 hover:opacity-75 transition font-medium text-sm focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="hidden sm:inline">Volver a Biblioteca</span>
                    <span class="sm:hidden">Volver</span>
                </button>
                
                <div class="text-sm font-bold truncate px-4 flex-1 text-center" x-text="bookTitle"></div>
                
                <div class="flex items-center gap-2">
                    <!-- Pantalla Completa -->
                    <button @click="toggleFullscreen" class="p-2 rounded-full transition hover:bg-gray-200 text-gray-600" title="Pantalla Completa">
                        <svg x-show="!isFullscreen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                        <svg x-show="isFullscreen" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h4v4M4 14l5 5M14 4h4v4m0-4l-5 5M4 10h4V6m-4 4l5-5m11 5h-4v4m4-4l-5 5"></path></svg>
                    </button>
                </div>
            </div>

            <div class="p-2 md:p-6 flex-1 flex flex-col bg-white">
                <div class="flex justify-between items-center flex-1">
                    <button @click="prevPage" class="p-2 rounded-full transition focus:outline-none focus:ring-2 focus:ring-indigo-500 z-10 relative bg-gray-100 text-gray-700 hover:bg-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    
                    <!-- Lector EPUB -->
                    <div id="viewer" class="w-full mx-2 md:mx-4 border-none overflow-hidden relative transition-colors duration-300" :style="isFullscreen ? 'height: 85vh; min-height: 100%;' : 'height: 70vh; min-height: 500px;'"></div>

                    <button @click="nextPage" class="p-2 rounded-full transition focus:outline-none focus:ring-2 focus:ring-indigo-500 z-10 relative bg-gray-100 text-gray-700 hover:bg-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
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
            readerOpen: false,
            bookTitle: '',
            bookId: null,
            book: null,
            rendition: null,
            isFullscreen: false,

            openBook(url, title, id) {
                this.bookTitle = title;
                this.bookId = id;
                this.readerOpen = true;
                
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.initEpub(url);
                    }, 300);
                });
            },

            closeBook() {
                this.readerOpen = false;
                this.isFullscreen = false;
                if(this.book) {
                    this.book.destroy();
                }
                document.getElementById("viewer").innerHTML = "";
            },

            toggleFullscreen() {
                this.isFullscreen = !this.isFullscreen;
                setTimeout(() => {
                    if (this.rendition) {
                        this.rendition.resize("100%", "100%");
                    }
                }, 300);
            },

            async initEpub(url) {
                document.getElementById("viewer").innerHTML = '<div class="absolute inset-0 flex flex-col items-center justify-center text-gray-500"><svg class="animate-spin h-8 w-8 mb-2 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Descargando libro...</div>';
                
                try {
                    const response = await fetch(url);
                    if (!response.ok) {
                        throw new Error("Error HTTP: " + response.status);
                    }
                    const bookData = await response.arrayBuffer();
                    
                    document.getElementById("viewer").innerHTML = "";
                    this.book = ePub(bookData);
                    
                    this.book.ready.then(() => {
                        this.rendition = this.book.renderTo("viewer", {
                            width: "100%",
                            height: "100%",
                            spread: "none",
                            manager: "continuous",
                            flow: "paginated"
                        });

                        const savedLocation = localStorage.getItem('epub-location-' + this.bookId);

                        this.rendition.display(savedLocation || undefined).catch(err => {
                            console.error("Error al mostrar:", err);
                            document.getElementById("viewer").innerHTML = '<div class="absolute inset-0 flex items-center justify-center text-red-500 font-bold p-4 text-center">No se pudo renderizar el libro. Es posible que el archivo no sea un EPUB válido.</div>';
                        });

                        this.rendition.on("relocated", (location) => {
                            localStorage.setItem('epub-location-' + this.bookId, location.start.cfi);
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
                    }).catch(err => {
                        throw err;
                    });
                } catch (err) {
                    console.error("Error al cargar libro:", err);
                    document.getElementById("viewer").innerHTML = '<div class="absolute inset-0 flex flex-col items-center justify-center text-red-500 font-bold p-4 text-center"><svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>Error al descargar el libro.<br><span class="text-sm font-normal mt-2 text-gray-600">Verifica que corriste "php artisan storage:link" en el servidor y que el archivo existe.</span></div>';
                }
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
