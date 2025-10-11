<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title>@yield('title', 'Marvelly')</title>
       
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
</head>
<body>
    <!-- ============================================-->
      
        @yield('content')
        
    <!-- ============================================-->
    <p class="">Designed by <a href="https://marvelly.com.ng">Marvelly</a> <script>document.write(new Date().getFullYear());</script></p>
</body>
</html>