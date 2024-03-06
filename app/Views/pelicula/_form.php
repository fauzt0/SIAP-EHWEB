<label for="titulo">Titulo</label>
<input type="text" name="titulo" id="titulo" placeholder="Titulo" value="<?php echo $pelicula['titulo']; ?>">
<br>
<label for="descripcion">Descripción</label>
<textarea name="descripcion" id="descripcion" placeholder="descripcion"><?php echo $pelicula['descripcion'] ?></textarea>

<br>
<button type="submit"><?php echo $op; ?></button>
