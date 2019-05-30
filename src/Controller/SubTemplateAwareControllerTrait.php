<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Skyline\Application\Controller;


use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Template\TemplateInterface;

trait SubTemplateAwareControllerTrait
{
    /**
     * Registers a template under the specified reuseIdentifier.
     * If the reuse identifier is empty, the template is registered under its id
     *
     * @param TemplateInterface $subTemplate
     * @param string $reuseIdentifier
     */
    protected function registerSubTemplate(TemplateInterface $subTemplate, string $reuseIdentifier = "") {
        if(!$reuseIdentifier)
            $reuseIdentifier = $subTemplate->getID();
        $list = $this->getRenderInfo()->get(RenderInfoInterface::INFO_SUB_TEMPLATES);
        $list[$reuseIdentifier] = $subTemplate;
        $this->getRenderInfo()->set(RenderInfoInterface::INFO_SUB_TEMPLATES, $list);
    }

    /**
     * Removes a sub template
     *
     * @param string $reuseIdentifier
     */
    protected function unregisterSubTemplate(string $reuseIdentifier) {
        $list = $this->getRenderInfo()->get(RenderInfoInterface::INFO_SUB_TEMPLATES);
        if(isset($list[$reuseIdentifier]))
            unset($list[$reuseIdentifier]);
        $this->getRenderInfo()->set(RenderInfoInterface::INFO_SUB_TEMPLATES, $list);
    }

    /**
     * Removes a sub template
     *
     * @param string $reuseIdentifier
     * @return TemplateInterface|null
     */
    protected function getSubTemplate(string $reuseIdentifier): ?TemplateInterface {
        $list = $this->getRenderInfo()->get(RenderInfoInterface::INFO_SUB_TEMPLATES);
        return $list[$reuseIdentifier] ?? NULL;
    }

    /**
     * @return RenderInfoInterface
     */
    public function getRenderInfo(): RenderInfoInterface
    {
        throw new \InvalidArgumentException("Method " . __METHOD__ . " must be overridden by class using trait");
    }
}