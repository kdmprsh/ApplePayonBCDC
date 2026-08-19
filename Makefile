REGISTRY := dockerhub.paypalcorp.com/capsule
IMAGE    := applepay-spb
TAG      := amd64-v10
PLATFORM := linux/amd64
BUILDER  := rancher-desktop

IMAGE_REF := $(REGISTRY)/$(IMAGE):$(TAG)

.PHONY: build push all

build:
	docker buildx build --builder $(BUILDER) --platform $(PLATFORM) -t $(IMAGE_REF) --load .

push:
	docker buildx build --builder $(BUILDER) --platform $(PLATFORM) -t $(IMAGE_REF) --push .

all: build push
