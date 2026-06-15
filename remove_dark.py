import os
import re

files = [
    'resources/views/dashboard.blade.php',
    'resources/views/livewire/admin/dashboard.blade.php',
    'resources/views/layouts/app.blade.php',
    'resources/views/layouts/guest.blade.php',
    'resources/views/livewire/layout/navigation.blade.php'
]

for f in files:
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # Remove dark: variants
    content = re.sub(r'dark:[a-zA-Z0-9\-\/]+', '', content)
    # Clean up double spaces created by removal
    content = re.sub(r' +', ' ', content)
    content = content.replace('\" >', '\">')
    content = content.replace('\" }', '\"}')
    content = content.replace('\" />', '\"/>')
    
    with open(f, 'w', encoding='utf-8') as file:
        file.write(content)
print('Removed dark variants.')
