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

        Book::create([
            'title' => $this->title,
            'author' => $this->author,
            'file_path' => $path,
        ]);

        $this->reset(['title', 'author', 'file', 'showUploadForm']);
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
                            <div class="md:col-span-2">
                                <x-input-label for="file" value="Archivo .epub *" />
                                <input type="file" wire:model="file" id="file" accept=".epub" class="mt-1 block w-full border border-gray-300 rounded-md p-2 text-sm" required>
                                <div wire:loading wire:target="file" class="text-sm text-indigo-600 mt-2">Subiendo archivo...</div>
                                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <x-primary-button wire:loading.attr="disabled" wire:target="saveBook, file">Guardar Libro</x-primary-button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                @forelse($books as $book)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition cursor-pointer relative group flex flex-col" 
                         @click="openBook('{{ asset('storage/' . $book->file_path) }}', '{{ addslashes($book->title) }}')">
                        <div class="aspect-[2/3] bg-gradient-to-br from-indigo-100 to-purple-100 flex flex-col items-center justify-center p-4 text-center">
                            <svg class="w-12 h-12 text-indigo-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <div class="p-3 flex-1 flex flex-col justify-center">
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-2 leading-tight">{{ $book->title }}</h3>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $book->author }}</p>
                        </div>
                        
                        @if(auth()->user()->role === 'admin')
                            <button wire:click.stop="deleteBook({{ $book->id }})" wire:confirm="¿Borrar este libro definitivamente?" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition shadow hover:bg-red-600 focus:opacity-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center bg-white rounded-lg shadow-sm border border-gray-100">
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
        <div x-show="readerOpen" style="display: none;" class="bg-white rounded-lg shadow-sm overflow-hidden" x-transition x-cloak>
            <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <button @click="closeBook" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition font-medium text-sm focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver a Biblioteca
                </button>
                <div class="text-sm font-bold text-gray-800 truncate px-4" x-text="bookTitle"></div>
                <div class="w-[120px]"></div> <!-- Spacer for perfect centering -->
            </div>

            <div class="p-2 md:p-6">
                <div class="flex justify-between items-center">
                    <button @click="prevPage" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-full text-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 z-10 relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    
                    <!-- Lector EPUB (Fix del alto) -->
                    <div id="viewer" class="w-full h-[65vh] md:h-[75vh] mx-2 md:mx-4 border border-gray-100 rounded-lg shadow-inner overflow-hidden relative bg-[#fdfdfd]"></div>

                    <button @click="nextPage" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-full text-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 z-10 relative">
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
            book: null,
            rendition: null,

            openBook(url, title) {
                this.bookTitle = title;
                this.readerOpen = true;
                
                // CRITICAL FIX: Ensure Alpine has rendered the div as visible BEFORE initializing ePub
                // so ePub.js can correctly calculate the container's height and width.
                this.$nextTick(() => {
                    this.initEpub(url);
                });
            },

            closeBook() {
                this.readerOpen = false;
                if(this.book) {
                    this.book.destroy();
                }
                document.getElementById("viewer").innerHTML = "";
            },

            initEpub(url) {
                document.getElementById("viewer").innerHTML = "";
                
                this.book = ePub(url);
                this.rendition = this.book.renderTo("viewer", {
                    width: "100%",
                    height: "100%",
                    spread: "none",
                    manager: "continuous",
                    flow: "paginated"
                });

                this.rendition.display();

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
