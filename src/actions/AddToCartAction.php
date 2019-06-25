<?php

/*
 * Cart module for Yii2
 *
 * @link      https://github.com/hiqdev/yii2-cart
 * @package   yii2-cart
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2016, HiQDev (http://hiqdev.com/)
 */

namespace hiqdev\yii2\cart\actions;

use hipanel\modules\finance\cart\AbstractCartPosition;
use hiqdev\hiart\Collection;
use hiqdev\yii2\cart\CartPositionInterface;
use hiqdev\yii2\cart\Module as CartModule;
use Yii;

class AddToCartAction extends \yii\base\Action
{
    /**
     * @var CartPositionInterface The class for new product
     */
    public $productClass;

    /**
     * @var boolean whether the action expects bulk models load using `selection`
     */
    public $bulkLoad = false;

    /**
     * @var bool whether client should be redirected to the cart in case of success item adding
     */
    public $redirectToCart = false;

    /**
     * @var bool whether any errors occurred during save
     */
    protected $hasErrors = false;

    /**
     * Returns the cart module.
     * @return CartModule
     */
    public function getCartModule()
    {
        return CartModule::getInstance();
    }

    public function run()
    {
        $data = null;
        $request = Yii::$app->request;
        /** @var CartPositionInterface $model */
        $model = Yii::createObject($this->productClass);
        $collection = new Collection(); // TODO: drop dependency
        $collection->setModel($model);

        if (!$this->bulkLoad) {
            $data = [$request->post() ?: $request->get()];
            $collection->load($data);
        } else {
            $collection->load();
        }

        $positions = [];
        foreach ($collection->models as $position) {
            /** @var CartPositionInterface|AbstractCartPosition $position */
            if (!$position->validate()) {
                $this->hasErrors = true;
                $error = $collection->getFirstError();
                if (empty($error)) {
                    $error = Yii::t('cart', 'Failed to add item to the cart');
                }
                Yii::$app->session->addFlash('warning', $error);
                Yii::warning('Failed to add item to cart', 'cart');

                continue;
            }

            $positions[] = $position;
        }

        $this->putPositionsToCart($positions);
    }

    protected function putPositionsToCart($positions)
    {
        $cart = $this->getCartModule()->getCart();
        $cart->putPositions($positions);
    }

    protected function afterRun()
    {
        $request = Yii::$app->request;

        if ($request->isAjax) {
            Yii::$app->end();
        }

        if ($this->redirectToCart && !$this->hasErrors) {
            return $this->controller->redirect('@cart');
        } elseif (isset($request->referrer)) {
            if (!$this->hasErrors) {
                Yii::$app->getSession()->setFlash('success', Yii::t('cart', 'Item has been added to cart'));
            }
            return $this->controller->redirect($request->referrer);
        } else {
            return $this->controller->goHome();
        }
    }
}
