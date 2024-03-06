<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\PeliculaModel;

class Pelicula extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $peliculamodel = new PeliculaModel();        
        echo view('/pelicula/index', ['peliculas' => $peliculamodel->findAll() ]);
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        $peliculamodel = new PeliculaModel();                
        echo view('/pelicula/show', ['pelicula' => $peliculamodel->find($id) ]);
    }

    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        
        
        $pelicula['titulo'] = 'title';
        $pelicula['descripcion'] = 'desc';

        $data['pelicula'] = $pelicula;

        echo view('/pelicula/new', $data);
        
    }

    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        $peliculamodel = new PeliculaModel();
        //save the data if not exist or update if exist
        $result = $peliculamodel -> insert([
            'titulo' => $this->request->getPost('titulo'),
            'descripcion' => $this->request->getPost('descripcion')
        ]);

        echo "Se creo la pelicula con el id: ".$result;        
    }

    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        $peliculamodel = new PeliculaModel();
        echo view('/pelicula/edit', ['pelicula' => $peliculamodel->find($id) ]);
    }

    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {
        $peliculamodel = new PeliculaModel();
        $peliculamodel -> update($id, [
            'titulo' => $this->request->getPost('titulo'),
            'descripcion' => $this->request->getPost('descripcion')
        ]); 

        echo "Se actualizo la pelicula con el id: ".$id;        

    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        
        
        $peliculamodel = new PeliculaModel();
        $peliculamodel->delete($id);

        echo "Deleted".$id;
    }
}
