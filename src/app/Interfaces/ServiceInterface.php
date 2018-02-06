<?php

namespace App\Interfaces;

interface ServiceInterface
{
    /**
     * @return ModelInterface
     */
    public function getModel();

    /**
     * @param array $input
     * @param ControllerInterface $controller
     * @return Illuminate\Http\Response
     */
    public function store(Array $input, ControllerInterface $controller);

    /**
     * @param array $input
     * @param ModelInterface $model
     * @param ControllerInterface $controller
     * @return Illuminate\Http\Response
     */
    public function update(Array $input, ModelInterface $model, ControllerInterface $controller);

    /**
     * @param ModelInterface $model
     * @param ControllerInterface $controller
     * @return Illuminate\Http\Response
     */
    public function destroy(ModelInterface $model, ControllerInterface $controller);
}