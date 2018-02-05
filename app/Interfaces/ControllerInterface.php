<?php

namespace App\Interfaces;

use Illuminate\Support\MessageBag;

interface ControllerInterface
{
    /**
     * @return \Illuminate\Pagination\Paginator
     */
    public function index();

    /**
     * @param ModelInterface $model
     * @return ModelInterface
     */
    public function show(ModelInterface $model);

    /**
     * Create a new user based in a GET or POST input.
     *
     * @return array
     */
    public function store();

    /**
     * @param ModelInterface $model
     * @param MessageBag $messages
     * @return mixed
     */
    public function storeError(ModelInterface $model, MessageBag $messages);

    /**
     * @param ModelInterface $model
     * @return mixed
     */
    public function storeSuccess(ModelInterface $model);

    /**
     * @param ModelInterface $model
     * @return \Illuminate\Http\Response
     */
    public function update(ModelInterface $model);

    /**
     * @param ModelInterface $model
     * @param MessageBag $messages
     * @return Response
     */
    public function updateError(ModelInterface $model, MessageBag $messages);

    /**
     * @param ModelInterface $model
     * @return mixed
     */
    public function updateSuccess(ModelInterface $model);

    /**
     * @param ModelInterface $model
     * @return \Illuminate\Http\Response
     */
    public function destroy(ModelInterface $model);

    /**
     * @return mixed
     */
    public function destroySuccess();
}