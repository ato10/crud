<?php

namespace Orchid\Crud;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ResourceRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        if ($this->method() === 'GET' || Str::endsWith($this->url(), 'delete')) {
            return [];
        }

        $model = $this->findModel() ?? $this->resource()->getModel();
        $rules = $this->resource()->rules($model);

        return collect($rules)
            ->mapWithKeys(function ($value, $key) {
                return ['model.' . $key => $value];
            })
            ->toArray();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return collect($this->model)->keys()
            ->mapWithKeys(function ($key) {
                return ['model.' . $key => $key];
            })
            ->merge($this->resource()->attributes())
            ->toArray();
    }

    /**
     * Validate the class instance.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages():array
    {
        return $this->resource()->messages();
    }

    /**
     * Configure the validator instance.
     *
     * @return void
     */
    public function withValidator()
    {
        $data = Arr::wrap($this->model);

        unset($this->model);

        $this->replace($data);
    }

    /**
     * Find the resource instance for the request.
     *
     * @return Resource
     */
    public function resource(): Resource
    {
        return $this->arbitrator()->findOrFail(
            $this->route('resource')
        );
    }

    /**
     * @return Arbitrator
     */
    public function arbitrator(): Arbitrator
    {
        return app(Arbitrator::class);
    }

    /**
     * @return Model
     */
    public function model(): Model
    {
        return $this->resource()->getModel();
    }

    /**
     * Find the model instance for the request.
     *
     * @return Model|null
     */
    public function findModel(): ?Model
    {
        return $this->getModelQuery()->find($this->route('id'));
    }

    /**
     * Find the model instance for the request.
     *
     * @return Model
     */
    public function findModelOrFail()
    {
        return $this->getModelQuery()->findOrFail($this->route('id'));
    }

    /**
     * @return Paginator
     */
    public function getModelPaginationList()
    {
        return $this->model()
            ->with($this->resource()->with())
            ->filters()
            ->filtersApply($this->resource()->filters())
            ->paginate($this->resource()->perPage());
    }

    /**
     * @return Model
     */
    private function getModelQuery()
    {
        $query = $this->model();

        if ($this->resource()->softDeletes()) {
            $query = $query->withTrashed();
        }

        return $query->with($this->resource()->with());
    }

    /**
     * Determine if the entity has a given ability.
     *
     * @param string $abilities
     *
     * @return bool
     */
    public function can(string $abilities): bool
    {
        $model = $this->route('id') === null
            ? $this->model()
            : $this->findModelOrFail();

        if (Gate::getPolicyFor($model) === null) {
            return true;
        }

        /** @var Authorizable|null $user */
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $this->user()->can($abilities, $model);
    }
}
