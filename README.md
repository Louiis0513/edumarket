# EduMarket

Marketplace escolar de útiles usados (libros, uniformes, maletas y más).

## URL
http://localhost/EduMarket/

## Importar SQL (instalación limpia)
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -h 127.0.0.1 -u root < bd/edumarket.sql
```

## Migrar base ya existente
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -h 127.0.0.1 -u root edumarket < bd/migracion_mejoras.sql
```

## Demo
| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin@demo.com | Admin123! | Administrador |
| maria@demo.com | Admin123! | Usuario |
| carlos@demo.com | Admin123! | Usuario |

Panel admin: http://localhost/EduMarket/admin.php (solo con cuenta admin).

## Funciones
- Publicar con **subida de foto**
- Categorías ampliadas
- Colegios existentes + crear nuevos (sin duplicados)
- Paginación en catálogo, mis publicaciones y admin
- Inicio muestra solo los **4** últimos artículos
- Panel admin: eliminar/controlar publicaciones, colegios y usuarios
