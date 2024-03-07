<h1>Readme Etailers - MiPago</h1>
<h2>Descripción:</h2>
En este módulo se crea un nuevo offline Payment Method, llamado MiPago. <br />
Los pedidos quedarán en Pending hasta que se facturen.<br />
Tenemos la opción de añadir un recargo por uso de este método de pago.

<h2>Instalación:</h2>
- Descargar el código de la rama master del repo https://github.com/davidredesit/etailers
- Copiarlo dentro de /app/code/
- Lanzar un bin/magento setup:upgrade
- Vaciar cachés

<h2>Uso:</h2>
Encontramos la configuración en el admin, en el apartado Stores - Configuration - Sales - Payment Methods - Other payment methods - Mi pago.
- Desde aquí podremos activar/desactivar la funcionalidad por website.
- Modificar la descripción visible desde el front.
- Configurar un recargo por uso de este meétodo de pago.

