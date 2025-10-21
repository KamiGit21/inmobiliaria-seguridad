# Instrucciones para configuración del proyecto inmobiliario

## 1. Instalación y configuración de XAMPP
1. Asegúrate de tener **XAMPP** instalado en tu máquina. Puedes descargarlo desde: [https://www.apachefriends.org/index.html](https://www.apachefriends.org/index.html).
2. Abre el **Panel de Control de XAMPP**.
3. Inicia los servicios **Apache** y **MySQL** haciendo clic en los botones **Start** correspondientes.

## 2. Creación de la base de datos
1. Accede a **phpMyAdmin** ingresando la siguiente URL en tu navegador:  
   [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. En **phpMyAdmin**, selecciona la opción **Base de Datos** en el menú superior.
3. Crea una nueva base de datos con el nombre:  
   ```
   droca
   ```

## 3. Importar archivo SQL en la base de datos
1. Dentro de **phpMyAdmin**, selecciona la base de datos **droca** que acabas de crear.
2. Haz clic en la pestaña **Importar**.
3. Selecciona el archivo SQL correspondiente al proyecto **droca** desde tu equipo.
4. Presiona el botón **Continuar** para finalizar la importación.

## 4. Descomprimir y copiar el proyecto en htdocs
1. Descomprime el archivo **inmobiliaria.zip**.
2. Copia la carpeta **inmobiliaria** en la ruta:  
   ```
   C:\xampp\htdocs
   ```

## 5. Acceder a la página del proyecto
1. Abre tu navegador y accede a la siguiente dirección:  
   ```
   http://localhost/inmobiliaria-seguridad
   ```
2. Verifica que la página se cargue correctamente.
